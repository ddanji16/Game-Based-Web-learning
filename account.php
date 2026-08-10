<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/Admin-folder/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['usertype']) || (int) $_SESSION['usertype'] !== 0) {
    header('Location: Form-folder/login.php');
    exit;
}

// Ensure the users table has a grade_level column (Grades 1-6).
mysqli_query($con, "ALTER TABLE users ADD COLUMN IF NOT EXISTS grade_level TINYINT NULL DEFAULT NULL");

function accountE($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function accountTableExists($connection, $table)
{
    $statement = mysqli_prepare(
        $connection,
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
    );
    mysqli_stmt_bind_param($statement, 's', $table);
    mysqli_stmt_execute($statement);

    return mysqli_num_rows(mysqli_stmt_get_result($statement)) > 0;
}

$studentId = (int) $_SESSION['user_id'];

$profileStatement = mysqli_prepare(
    $con,
    'SELECT Firstname, Middlename, Lastname, Email, grade_level FROM users WHERE id = ? LIMIT 1'
);
mysqli_stmt_bind_param($profileStatement, 'i', $studentId);
mysqli_stmt_execute($profileStatement);
$profile = mysqli_fetch_assoc(mysqli_stmt_get_result($profileStatement));

if (!$profile) {
    session_destroy();
    header('Location: Form-folder/login.php');
    exit;
}

$courses = [];
if (accountTableExists($con, 'enrollments')) {
    $courseStatement = mysqli_prepare(
        $con,
        "SELECT c.course_code, c.title, c.description
         FROM enrollments e
         JOIN courses c ON c.id = e.course_id
         WHERE e.student_id = ? AND c.status = 'active'
         ORDER BY c.title"
    );
    mysqli_stmt_bind_param($courseStatement, 'i', $studentId);
    mysqli_stmt_execute($courseStatement);
    $courses = mysqli_fetch_all(mysqli_stmt_get_result($courseStatement), MYSQLI_ASSOC);
}

$progressRecords = [];
$averageProgress = 0;
$completedCount = 0;
if (accountTableExists($con, 'student_progress')) {
    $progressStatement = mysqli_prepare(
        $con,
        'SELECT activity_title, progress_percentage, score, status, updated_at
         FROM student_progress
         WHERE student_id = ?
         ORDER BY updated_at DESC'
    );
    mysqli_stmt_bind_param($progressStatement, 'i', $studentId);
    mysqli_stmt_execute($progressStatement);
    $progressRecords = mysqli_fetch_all(mysqli_stmt_get_result($progressStatement), MYSQLI_ASSOC);

    if ($progressRecords) {
        $averageProgress = (int) round(array_sum(array_column($progressRecords, 'progress_percentage')) / count($progressRecords));
        $completedCount = count(array_filter($progressRecords, fn($record) => $record['status'] === 'completed'));
    }
}

$certificates = [];
if (accountTableExists($con, 'certificates')) {
    $certificateStatement = mysqli_prepare(
        $con,
        'SELECT c.id, c.certificate_type, c.cert_code, c.course_id, co.title AS course_title
         FROM certificates c
         LEFT JOIN courses co ON co.id = c.course_id
         WHERE c.student_id = ?
         ORDER BY c.id DESC'
    );
    mysqli_stmt_bind_param($certificateStatement, 'i', $studentId);
    mysqli_stmt_execute($certificateStatement);
    $certificates = mysqli_fetch_all(mysqli_stmt_get_result($certificateStatement), MYSQLI_ASSOC);
}

$fullName = trim($profile['Firstname'] . ' ' . $profile['Middlename'] . ' ' . $profile['Lastname']);
$initial = strtoupper(substr($profile['Firstname'], 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Account | Jidanao LMS</title>
    <link rel="stylesheet" href="account.css?v=<?= filemtime(__DIR__ . '/account.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <header class="topbar">
        <a href="index.php" class="brand"><span><i class="fa-solid fa-graduation-cap"></i></span>Jidanao <b>LMS</b></a>
        <nav><a href="index.php"><i class="fa-solid fa-house"></i> Home</a><a class="logout" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></nav>
    </header>
    <main>
        <section class="welcome-card">
            <span class="avatar"><?= accountE($initial) ?></span>
            <div><p>STUDENT ACCOUNT</p><h1>Hello, <?= accountE($profile['Firstname']) ?>!</h1><span><?= accountE($profile['Email']) ?></span></div>
            <a href="index.php#learning-modules">Explore modules <i class="fa-solid fa-arrow-right"></i></a>
        </section>
        <section class="summary-grid">
            <article><i class="fa-solid fa-chart-line blue"></i><div><small>Average progress</small><strong><?= $averageProgress ?>%</strong></div></article>
            <article><i class="fa-solid fa-circle-check green"></i><div><small>Completed activities</small><strong><?= $completedCount ?></strong></div></article>
            <article><i class="fa-solid fa-book-open orange"></i><div><small>My courses</small><strong><?= count($courses) ?></strong></div></article>
        </section>
<section class="content-grid">
<article class="card"><div class="heading"><div><p>MY INFORMATION</p><h2>Profile details</h2></div><i class="fa-solid fa-id-card"></i></div><dl><div><dt>Full name</dt><dd><?= accountE(trim($profile['Firstname'].' '.$profile['Middlename'].' '.$profile['Lastname'])) ?></dd></div><div><dt>Email address</dt><dd><?= accountE($profile['Email']) ?></dd></div><div><dt>Role</dt><dd>Student</dd></div><div><dt>Grade level</dt><dd><?= $profile['grade_level'] ? 'Grade '.accountE($profile['grade_level']) : 'Not set' ?></dd></div></dl></article>
            <article class="card"><div class="heading"><div><p>MY LEARNING</p><h2>Progress overview</h2></div><i class="fa-solid fa-medal"></i></div><div class="circle" style="--progress: <?= $averageProgress ?>"><span><?= $averageProgress ?>%<small>overall</small></span></div><p class="center-text">Keep going! Every activity helps you grow.</p></article>
        </section>
        <section class="card"><div class="heading"><div><p>ENROLLED COURSES</p><h2>My courses</h2></div></div><div class="course-grid"><?php if ($courses): foreach ($courses as $course): ?><article><span><i class="fa-solid fa-book"></i></span><p><?= accountE($course['course_code']) ?></p><h3><?= accountE($course['title']) ?></h3><small><?= accountE($course['description'] ?: 'Your learning space is ready.') ?></small></article><?php endforeach; else: ?><div class="empty"><i class="fa-solid fa-book-open"></i><h3>No courses yet</h3><p>Your teacher or administrator will enroll you in a course soon.</p></div><?php endif; ?></div></section>
        <section class="card"><div class="heading"><div><p>ACTIVITY RECORDS</p><h2>My progress</h2></div></div><div class="table-wrap"><table><thead><tr><th>Activity</th><th>Progress</th><th>Score</th><th>Status</th><th>Updated</th></tr></thead><tbody><?php if ($progressRecords): foreach ($progressRecords as $record): ?><tr><td><b><?= accountE($record['activity_title']) ?></b></td><td><div class="progress"><i style="width: <?= (int)$record['progress_percentage'] ?>%"></i></div><?= (int)$record['progress_percentage'] ?>%</td><td><?= $record['score'] !== null ? accountE($record['score']).'%' : '—' ?></td><td><span class="status <?= accountE($record['status']) ?>"><?= accountE(ucwords(str_replace('_', ' ', $record['status']))) ?></span></td><td><?= accountE(date('M j, Y', strtotime($record['updated_at']))) ?></td></tr><?php endforeach; else: ?><tr><td colspan="5" class="no-records">Your teacher has not posted a progress record yet.</td></tr><?php endif; ?></tbody></table></div></section>
        <section class="card">
            <div class="heading">
                <div><p>MY CERTIFICATES</p><h2>Available downloads</h2></div>
                <i class="fa-solid fa-award"></i>
            </div>
            <?php if ($certificates): foreach ($certificates as $certificate): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid #e5e7eb;">
                    <div>
                        <strong><?= accountE($certificate['certificate_type']) ?></strong>
                        <p style="margin:4px 0; color:#4b5563;"><?= accountE($certificate['course_title'] ?: 'Course certificate') ?></p>
                        <small>Code: <?= accountE($certificate['cert_code']) ?></small>
                    </div>
                    <button type="button" style="padding:8px 12px; background:#2563eb; color:#fff; border:none; border-radius:8px; cursor:pointer;" onclick="downloadCertificate(<?= json_encode($fullName, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>, <?= json_encode($certificate['course_title'] ?: 'Course', JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>, <?= json_encode($certificate['certificate_type'], JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>)">Download Certificate</button>
                </div>
            <?php endforeach; else: ?>
                <div class="empty"><i class="fa-solid fa-award"></i><h3>No certificates yet</h3><p>Your achievements will appear here once your teacher or administrator issues one.</p></div>
            <?php endif; ?>
        </section>
    </main>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.7/jspdf.umd.min.js"></script>
    <script>
        function downloadCertificate(studentName, courseName, certType) {
            const PdfLibrary = (window.jspdf && window.jspdf.jsPDF) ? window.jspdf.jsPDF : window.jsPDF;
            if (!PdfLibrary) {
                alert('PDF library is not available right now.');
                return;
            }

            const doc = new PdfLibrary({ orientation: 'landscape' });
            doc.setFontSize(34);
            doc.text('CERTIFICATE OF COMPLETION', 35, 45);
            doc.setFontSize(18);
            doc.text('This is to certify that', 105, 80, null, null, 'center');
            doc.setFontSize(24);
            doc.text(studentName || 'Student Name', 105, 100, null, null, 'center');
            doc.setFontSize(16);
            doc.text(`has successfully completed the ${certType || 'achievement'} of`, 105, 125, null, null, 'center');
            doc.text(courseName || 'Course Name', 105, 140, null, null, 'center');
            doc.save(`${(studentName || 'student').replace(/\s+/g, '_')}_Certificate.pdf`);
        }
    </script>
</body>
</html>
