<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['usertype']) || (int) $_SESSION['usertype'] !== 1) {
    header('Location: ../Form-folder/login.php');
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

mysqli_query($con, "CREATE TABLE IF NOT EXISTS student_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    activity_title VARCHAR(150) NOT NULL,
    progress_percentage TINYINT UNSIGNED NOT NULL DEFAULT 0,
    score DECIMAL(5,2) NULL,
    status ENUM('not_started', 'in_progress', 'completed') NOT NULL DEFAULT 'not_started',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'generate_cert') {
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $type = trim((string) ($_POST['cert_type'] ?? ''));

    if ($studentId > 0 && $courseId > 0 && $type !== '') {
        $certCode = 'CERT-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = mysqli_prepare($con, "INSERT INTO certificates (student_id, course_id, certificate_type, cert_code) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'iiss', $studentId, $courseId, $type, $certCode);

        if (mysqli_stmt_execute($stmt)) {
            $msg = "Congratulations! Your certificate for $type is ready for download.";
            $notif = mysqli_prepare($con, "INSERT INTO notifications (title, message, audience) VALUES (?, ?, 'students')");
            $title = 'Achievement Unlocked!';
            mysqli_stmt_bind_param($notif, 'ss', $title, $msg);
            mysqli_stmt_execute($notif);
            $_SESSION['flash'] = 'Certificate generated and student notified!';
        } else {
            $_SESSION['flash'] = 'Could not generate certificate.';
        }
    } else {
        $_SESSION['flash'] = 'Please choose a student and course before generating a certificate.';
    }

    header('Location: achievements.php');
    exit;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$progressQuery = mysqli_query($con, "
    SELECT
        u.id AS student_id,
        u.Firstname,
        u.Lastname,
        c.title AS course_name,
        c.id AS course_id,
        COALESCE((
            SELECT COUNT(*)
            FROM student_progress sp
            WHERE sp.student_id = u.id
              AND sp.status = 'completed'
        ), 0) AS completed_tasks,
        COALESCE((
            SELECT COUNT(*)
            FROM student_progress sp
            WHERE sp.student_id = u.id
        ), 0) AS total_tasks
    FROM users u
    JOIN enrollments e ON u.id = e.student_id
    JOIN courses c ON e.course_id = c.id
    WHERE u.UserType = 0
    ORDER BY u.Firstname, u.Lastname, c.title
");
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>Achievements | Admin</title>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="Dashboard.php"><span class="brand-mark">J</span><span>Jidanao <b>LMS</b></span></a>
            <nav>
                <a href="Dashboard.php"><i class="fa-solid fa-grid-2"></i> Overview</a>
                <a href="Dashboard.php#courses"><i class="fa-solid fa-book-open"></i> Courses</a>
                <a href="users.php"><i class="fa-solid fa-users"></i> Users</a>
                <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
                <a href="notifications.php"><i class="fa-solid fa-bell"></i> Announcement</a>
                 <a class="active" href="achievements.php"><i class="fa-solid fa-trophy"></i> Achievements</a>
                <a href="activity.php"><i class="fa-solid fa-clock-rotate-left"></i> Activity logs</a>
               
            </nav>
            <a class="logout" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a>
        </aside>
        <main>
            <header>
                <button class="menu-btn" id="menuButton" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                <div>
                    <p class="eyebrow">ADMINISTRATOR PORTAL</p>
                    <h1>Student Achievements & Certificates</h1>
                    <p class="subtle">Manage learner progress and issue certificates.</p>
                </div>
            </header>

            <?php if ($flash !== ''): ?>
                <div class="flash success" style="margin-bottom: 16px; padding: 10px; background: #e8f9ef; color: #17633a; border-radius: 6px;">
                    <?= e($flash) ?>
                </div>
            <?php endif; ?>

            <section class="panel wide">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Progress</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($progressQuery)):
                            $percent = ($row['total_tasks'] > 0) ? ($row['completed_tasks'] / $row['total_tasks']) * 100 : 0;
                        ?>
                        <tr>
                            <td><?= e($row['Firstname']) ?> <?= e($row['Lastname']) ?></td>
                            <td><?= e($row['course_name']) ?></td>
                            <td>
                                <div style="background:#eee; height:10px; border-radius:5px; width:100px;">
                                    <div style="background:#16a34a; height:100%; width:<?= (int) round($percent) ?>%; border-radius:5px;"></div>
                                </div>
                                <small><?= (int) $row['completed_tasks'] ?>/<?= (int) $row['total_tasks'] ?> Progress entries</small>
                            </td>
                            <td>
                                <form method="POST" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                    <input type="hidden" name="student_id" value="<?= (int) $row['student_id'] ?>">
                                    <input type="hidden" name="course_id" value="<?= (int) $row['course_id'] ?>">
                                    <select name="cert_type" style="padding:8px 10px; border:1px solid #d1d5db; border-radius:8px;">
                                        <option value="1st Quarter">1st Quarter</option>
                                        <option value="2nd Quarter">2nd Quarter</option>
                                        <option value="3rd Quarter">3rd Quarter</option>
                                        <option value="4th Quarter">4th Quarter</option>
                                        <option value="Full Course">Full Course</option>
                                    </select>
                                    <button name="action" value="generate_cert" class="primary" style="padding:8px 12px; display:inline-flex; align-items:center; gap:6px;">
                                        <i class="fa-solid fa-award"></i> Generate
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>