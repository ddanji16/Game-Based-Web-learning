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
        'SELECT c.id, c.certificate_type, c.course_name, c.certificate_title, c.certificate_message, c.certificate_period, c.cert_code, c.course_id, c.issue_date, c.issuer_name, co.title AS course_title
         FROM certificates c
         LEFT JOIN courses co ON co.id = c.course_id
         WHERE c.student_id = ?
         ORDER BY c.id DESC'
    );
    mysqli_stmt_bind_param($certificateStatement, 'i', $studentId);
    mysqli_stmt_execute($certificateStatement);
    $certificates = mysqli_fetch_all(mysqli_stmt_get_result($certificateStatement), MYSQLI_ASSOC);
}

$latestCertificate = $certificates[0] ?? null;

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
              <nav><a href="./Student-folder/index.php"><i class="fa-solid fa-house"></i>My Achievements</a>
        <nav><a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
        <a class="logout" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></nav>
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
            <p class="cert-note">Tap a button below to download the certificate as a PDF, JPG, or JPEG image.</p>
            <?php if ($latestCertificate): ?>
                <?php
                    $latestTitle = $latestCertificate['course_name'] ?: $latestCertificate['course_title'] ?: $latestCertificate['certificate_type'];
                    $latestTitle = $latestCertificate['certificate_title'] ?: $latestTitle;
                    $latestIssuedOn = !empty($latestCertificate['issue_date']) ? date('M d, Y', strtotime((string) $latestCertificate['issue_date'])) : 'Recently issued';
                    $latestIssuer = $latestCertificate['issuer_name'] ?: 'Jidanao LMS Admin';
                ?>
                <div class="download-banner">
                    <div>
                        <p>LATEST CERTIFICATE</p>
                        <h3><?= accountE($latestTitle) ?></h3>
                        <span>Issued on <?= accountE($latestIssuedOn) ?> by <?= accountE($latestIssuer) ?></span>
                        <?php if (!empty($latestCertificate['certificate_period'])): ?>
                            <span><?= accountE($latestCertificate['certificate_period']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="download-banner__actions cert-actions cert-actions--banner">
                        <button
                            type="button"
                            class="download-btn download-btn--pdf download-btn--hero"
                            onclick="downloadCertificatePdf(
                                <?= json_encode($fullName, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestTitle, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestCertificate['certificate_message'] ?? '', JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestCertificate['certificate_period'] ?? '', JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestCertificate['cert_code'], JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestIssuedOn, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestIssuer, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>
                            )"
                        >
                            <i class="fa-solid fa-download"></i> Download Certificate
                        </button>
                        <button
                            type="button"
                            class="download-btn download-btn--light"
                            onclick="downloadCertificateImage(
                                <?= json_encode($fullName, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestTitle, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestCertificate['certificate_message'] ?? '', JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestCertificate['certificate_period'] ?? '', JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestCertificate['cert_code'], JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestIssuedOn, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestIssuer, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                'jpg'
                            )"
                        >
                            <i class="fa-solid fa-image"></i> Download JPG
                        </button>
                        <button
                            type="button"
                            class="download-btn download-btn--light"
                            onclick="openCertificateModal(
                                <?= json_encode($fullName, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestTitle, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestCertificate['certificate_message'] ?? '', JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestCertificate['certificate_period'] ?? '', JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestCertificate['cert_code'], JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestIssuedOn, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($latestIssuer, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>
                            )"
                        >
                            <i class="fa-solid fa-eye"></i> Preview
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($certificates): foreach ($certificates as $certificate): ?>
                <?php
                    $certificateTitle = $certificate['course_name'] ?: $certificate['course_title'] ?: $certificate['certificate_type'];
                    $certificateTitle = $certificate['certificate_title'] ?: $certificateTitle;
                    $issuedOn = !empty($certificate['issue_date']) ? date('M d, Y', strtotime((string) $certificate['issue_date'])) : 'Recently issued';
                    $issuerName = $certificate['issuer_name'] ?: 'Jidanao LMS Admin';
                ?>
                <article class="cert-card">
                    <div class="cert-card__body">
                        <div class="cert-card__title">
                        <strong><?= accountE($certificateTitle) ?></strong>
                        <p style="margin:4px 0; color:#4b5563;">Issued by <?= accountE($issuerName) ?></p>
                        <?php if (!empty($certificate['certificate_period'])): ?>
                            <p style="margin:0 0 4px; color:#7c5a2d; font-weight:700;"><?= accountE($certificate['certificate_period']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($certificate['certificate_message'])): ?>
                            <small><?= accountE($certificate['certificate_message']) ?></small><br>
                        <?php endif; ?>
                        <small>Issued on <?= accountE($issuedOn) ?></small><br>
                        <small>Certificate code: <?= accountE($certificate['cert_code']) ?></small>
                    </div>
                    <div class="cert-actions">
                        <button
                            type="button"
                            class="download-btn download-btn--pdf download-btn--hero"
                            onclick="downloadCertificatePdf(
                                <?= json_encode($fullName, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($certificateTitle, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($certificate['certificate_message'] ?? '', JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($certificate['certificate_period'] ?? '', JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($certificate['cert_code'], JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($issuedOn, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($issuerName, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>
                            )"
                        >
                            <i class="fa-solid fa-download"></i> Download Certificate
                        </button>
                        <button
                            type="button"
                            class="download-btn download-btn--light"
                            onclick="openCertificateModal(
                                <?= json_encode($fullName, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($certificateTitle, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($certificate['certificate_message'] ?? '', JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($certificate['certificate_period'] ?? '', JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($certificate['cert_code'], JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($issuedOn, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>,
                                <?= json_encode($issuerName, JSON_HEX_SINGLE_QUOTES | JSON_HEX_DOUBLE_QUOTES) ?>
                            )"
                        >
                            <i class="fa-solid fa-eye"></i> Preview
                        </button>
                    </div>
                    </div>
                </article>
            <?php endforeach; else: ?>
                <div class="empty"><i class="fa-solid fa-award"></i><h3>No certificates yet</h3><p>Your achievements will appear here once your teacher or administrator issues one.</p></div>
            <?php endif; ?>
        </section>
        <section class="card" style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div>
                <div class="heading" style="margin-bottom:6px;"><div><p>ACHIEVEMENT PORTAL</p><h2>Open your full certificates page</h2></div></div>
                <p style="color:#4b5563; margin:0;">See every certificate and download them from one place.</p>
            </div>
            <a href="my_achievements.php" style="padding:10px 14px; background:#17213a; color:#fff; text-decoration:none; border-radius:10px; font-weight:700;">Open My Achievements</a>
        </section>
    </main>
    <div class="cert-modal" id="certModal" aria-hidden="true">
        <div class="cert-modal__overlay" data-cert-close></div>
        <div class="cert-modal__dialog" role="dialog" aria-modal="true" aria-label="Certificate preview">
            <div class="cert-modal__card">  
                <canvas id="certPreviewCanvas" width="1600" height="1131"></canvas>
            </div>
            <div class="cert-modal__footer">
                <button type="button" class="download-btn download-btn--light" data-cert-close>
                    <i class="fa-solid fa-xmark"></i> Close
                </button>
                <button type="button" class="download-btn download-btn--pdf" id="certPdfButton">
                    <i class="fa-solid fa-file-pdf"></i> Print / Download PDF
                </button>
                <button type="button" class="download-btn download-btn--image" id="certJpgButton">
                    <i class="fa-solid fa-image"></i> Download JPG
                </button>
                <button type="button" class="download-btn download-btn--jpg" id="certJpegButton">
                    <i class="fa-solid fa-image"></i> Download JPEG
                </button>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.7/jspdf.umd.min.js"></script>
    <script>
        const certModal = document.getElementById('certModal');
        const certCanvas = document.getElementById('certPreviewCanvas');
        const certPdfButton = document.getElementById('certPdfButton');
        const certJpgButton = document.getElementById('certJpgButton');
        const certJpegButton = document.getElementById('certJpegButton');
        let activeCertificate = null;

        function normalizeCertificateData(studentName, courseName, certificateMessage, certificatePeriod, certCode, issueDate, issuerName) {
            return {
                studentName: studentName || 'Student Name',
                courseName: courseName || 'Achievement',
                certificateMessage: certificateMessage || '',
                certificatePeriod: certificatePeriod || '',
                certCode: certCode || 'N/A',
                issueDate: issueDate || 'Recently issued',
                issuerName: issuerName || 'Jidanao LMS Admin',
            };
        }

        function getCertificateTitle(courseName) {
            const raw = (courseName || 'Reading').trim();
            return `CERTIFICATE OF ${raw.toUpperCase()} EXCELLENCE`;
        }

        function splitText(ctx, text, maxWidth) {
            const words = String(text || '').split(/\s+/).filter(Boolean);
            const lines = [];
            let line = '';

            for (const word of words) {
                const testLine = line ? `${line} ${word}` : word;
                if (ctx.measureText(testLine).width <= maxWidth || !line) {
                    line = testLine;
                } else {
                    lines.push(line);
                    line = word;
                }
            }

            if (line) {
                lines.push(line);
            }

            return lines;
        }

        function drawSchoolIcon(ctx, x, y, scale) {
            const w = 120 * scale;
            const h = 84 * scale;

            ctx.fillStyle = '#d8a93f';
            ctx.beginPath();
            ctx.moveTo(x + w / 2, y);
            ctx.lineTo(x + w, y + h * 0.38);
            ctx.lineTo(x, y + h * 0.38);
            ctx.closePath();
            ctx.fill();

            ctx.fillStyle = '#9d6b2f';
            ctx.fillRect(x + w * 0.15, y + h * 0.33, w * 0.7, h * 0.52);

            ctx.fillStyle = '#f8f0de';
            ctx.fillRect(x + w * 0.3, y + h * 0.48, w * 0.14, h * 0.22);
            ctx.fillRect(x + w * 0.56, y + h * 0.48, w * 0.14, h * 0.22);
            ctx.fillRect(x + w * 0.44, y + h * 0.60, w * 0.12, h * 0.25);
        }

        function drawCertificate(context, certificate) {
            const canvas = context.canvas;
            const w = canvas.width;
            const h = canvas.height;
            const data = normalizeCertificateData(
                certificate.studentName,
                certificate.courseName,
                certificate.certificateMessage,
                certificate.certificatePeriod,
                certificate.certCode,
                certificate.issueDate,
                certificate.issuerName,
            );

            context.clearRect(0, 0, w, h);
            context.fillStyle = '#f8f2df';
            context.fillRect(0, 0, w, h);

            const cardX = 80;
            const cardY = 70;
            const cardW = w - 160;
            const cardH = h - 140;

            context.fillStyle = '#fffdf6';
            context.fillRect(cardX, cardY, cardW, cardH);

            context.strokeStyle = '#d7a32e';
            context.lineWidth = 7;
            context.strokeRect(cardX, cardY, cardW, cardH);
            context.strokeStyle = '#e7c865';
            context.lineWidth = 2.5;
            context.strokeRect(cardX + 16, cardY + 16, cardW - 32, cardH - 32);
            context.strokeStyle = '#e8d48d';
            context.lineWidth = 2;
            context.strokeRect(cardX + 42, cardY + 42, cardW - 84, cardH - 84);

            drawSchoolIcon(context, w / 2 - 38, cardY + 72, 0.8);

            context.textAlign = 'center';
            context.fillStyle = '#8b5a2b';
            context.font = '700 28px Arial, sans-serif';
            context.fillText('JIDANAO ELEMENTARY SCHOOL', w / 2, cardY + 182);

            const title = data.courseName && data.courseName.toLowerCase().includes('certificate')
                ? data.courseName
                : getCertificateTitle(data.courseName);
            context.fillStyle = '#8b5a2b';
            context.font = '700 54px Georgia, "Times New Roman", serif';
            const titleLines = splitText(context, title, cardW - 180);
            const titleStartY = cardY + 235;
            titleLines.slice(0, 2).forEach((line, index) => {
                context.fillText(line, w / 2, titleStartY + index * 58);
            });

            context.fillStyle = '#5f4630';
            context.font = '500 24px Arial, sans-serif';
            context.fillText('This certifies that', w / 2, cardY + 355);

            context.fillStyle = '#d6781f';
            context.font = '700 46px Georgia, "Times New Roman", serif';
            context.fillText(data.studentName, w / 2, cardY + 415);

            context.fillStyle = '#5f4630';
            context.font = '500 24px Arial, sans-serif';
            const bodyText = data.certificateMessage || `was awarded for outstanding ${data.courseName} performance and conceptual milestone mastery.`;
            const bodyLines = splitText(context, bodyText, cardW - 220);
            bodyLines.forEach((line, index) => {
                context.fillText(line, w / 2, cardY + 475 + index * 30);
            });

            context.strokeStyle = '#cba24a';
            context.lineWidth = 2;
            context.beginPath();
            context.moveTo(cardX + 220, cardY + 575);
            context.lineTo(cardX + 430, cardY + 575);
            context.moveTo(cardX + cardW - 430, cardY + 575);
            context.lineTo(cardX + cardW - 220, cardY + 575);
            context.stroke();

            context.fillStyle = '#6d4b22';
            context.font = '700 18px Arial, sans-serif';
            context.fillText(data.certificatePeriod ? 'PERIOD' : 'ISSUED ON', cardX + 325, cardY + 610);
            context.fillText(data.certificatePeriod || data.issueDate, cardX + 325, cardY + 637);
            context.fillText('CERTIFICATE CODE', cardX + cardW - 325, cardY + 610);
            context.fillText(data.certCode, cardX + cardW - 325, cardY + 637);

            if (!data.certificatePeriod) {
                context.fillStyle = '#7f5d30';
                context.font = '500 18px Arial, sans-serif';
                context.fillText(`Issued on ${data.issueDate}`, cardX + 325, cardY + 665);
            }

            context.strokeStyle = '#caa34b';
            context.lineWidth = 1.7;
            context.beginPath();
            context.moveTo(cardX + 190, cardY + cardH - 165);
            context.lineTo(cardX + 400, cardY + cardH - 165);
            context.moveTo(cardX + cardW - 400, cardY + cardH - 165);
            context.lineTo(cardX + cardW - 190, cardY + cardH - 165);
            context.stroke();

            context.fillStyle = '#6f4a2a';
            context.font = '700 20px Arial, sans-serif';
            context.fillText('CLASS ADVISER', cardX + 295, cardY + cardH - 130);
            context.fillText('SCHOOL PRINCIPAL', cardX + cardW - 295, cardY + cardH - 130);

            context.fillStyle = '#9f7a42';
            context.font = '500 18px Arial, sans-serif';
            context.fillText(data.issuerName, w / 2, cardY + cardH - 52);
        }

        function renderCertificateToCanvas(certificate, canvasElement = certCanvas) {
            const context = canvasElement.getContext('2d');
            drawCertificate(context, certificate);
            return canvasElement;
        }

        function openCertificateModal(studentName, courseName, certificateMessage, certificatePeriod, certCode, issueDate, issuerName) {
            activeCertificate = normalizeCertificateData(studentName, courseName, certificateMessage, certificatePeriod, certCode, issueDate, issuerName);
            renderCertificateToCanvas(activeCertificate, certCanvas);
            certModal.classList.add('open');
            certModal.setAttribute('aria-hidden', 'false');
        }

        function closeCertificateModal() {
            certModal.classList.remove('open');
            certModal.setAttribute('aria-hidden', 'true');
        }

        function downloadCertificatePdf(studentName, courseName, certificateMessage, certificatePeriod, certCode, issueDate, issuerName) {
            const PdfLibrary = (window.jspdf && window.jspdf.jsPDF) ? window.jspdf.jsPDF : window.jsPDF;
            if (!PdfLibrary) {
                alert('PDF library is not available right now.');
                return;
            }

            const data = normalizeCertificateData(studentName, courseName, certificateMessage, certificatePeriod, certCode, issueDate, issuerName);
            const doc = new PdfLibrary({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            const canvas = document.createElement('canvas');
            canvas.width = 1600;
            canvas.height = 1131;
            drawCertificate(canvas.getContext('2d'), data);
            const imgData = canvas.toDataURL('image/png');
            doc.addImage(imgData, 'PNG', 0, 0, 297, 210);

            const safeStudent = (data.studentName || 'student').replace(/\s+/g, '_');
            const safeCourse = (data.courseName || 'certificate').replace(/\s+/g, '_');
            doc.save(`${safeStudent}_${safeCourse}.pdf`);
        }

        function downloadCertificateImage(studentName, courseName, certificateMessage, certificatePeriod, certCode, issueDate, issuerName, format = 'png') {
            const data = normalizeCertificateData(studentName, courseName, certificateMessage, certificatePeriod, certCode, issueDate, issuerName);
            const canvas = document.createElement('canvas');
            canvas.width = 1600;
            canvas.height = 1131;
            drawCertificate(canvas.getContext('2d'), data);
            const link = document.createElement('a');
            const safeStudent = (data.studentName || 'student').replace(/\s+/g, '_');
            const safeCourse = (data.courseName || 'certificate').replace(/\s+/g, '_');
            const isJpeg = format === 'jpg' || format === 'jpeg';
            const mimeType = isJpeg ? 'image/jpeg' : 'image/png';
            const extension = isJpeg ? (format === 'jpeg' ? 'jpeg' : 'jpg') : 'png';
            link.download = `${safeStudent}_${safeCourse}.${extension}`;
            link.href = canvas.toDataURL(mimeType, 0.95);
            link.click();
        }

        function downloadCurrentCertificate(format) {
            if (!activeCertificate) {
                return;
            }

            if (format === 'pdf') {
                downloadCertificatePdf(
                    activeCertificate.studentName,
                    activeCertificate.courseName,
                    activeCertificate.certificateMessage,
                    activeCertificate.certificatePeriod,
                    activeCertificate.certCode,
                    activeCertificate.issueDate,
                    activeCertificate.issuerName,
                );
                return;
            }

            downloadCertificateImage(
                activeCertificate.studentName,
                activeCertificate.courseName,
                activeCertificate.certificateMessage,
                activeCertificate.certificatePeriod,
                activeCertificate.certCode,
                activeCertificate.issueDate,
                activeCertificate.issuerName,
                format,
            );
        }

        certModal.addEventListener('click', (event) => {
            if (event.target.closest('[data-cert-close]') || event.target === certModal) {
                closeCertificateModal();
            }
        });

        certPdfButton.addEventListener('click', () => downloadCurrentCertificate('pdf'));
        certJpgButton.addEventListener('click', () => downloadCurrentCertificate('jpg'));
        certJpegButton.addEventListener('click', () => downloadCurrentCertificate('jpeg'));
    </script>
</body>
</html>
