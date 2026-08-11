<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/database.php';

if (!isset($_SESSION['usertype']) || (int) $_SESSION['usertype'] !== 1) {
    header('Location: ../Form-folder/login.php');
    exit;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$totalActivities = 20;
$students = mysqli_query(
    $con,
    "SELECT id, Firstname, Lastname
     FROM users
     WHERE UserType = 0
     ORDER BY Firstname, Lastname"
);

$courses = mysqli_query(
    $con,
    "SELECT id, course_code, title
     FROM courses
     WHERE status = 'active'
     ORDER BY title"
);

$progressQuery = mysqli_query(
    $con,
    "
    SELECT
        u.id AS student_id,
        u.Firstname,
        u.Lastname,
        COUNT(DISTINCT p.activity_title) AS tracked_activities,
        COUNT(DISTINCT CASE WHEN p.status = 'completed' THEN p.activity_title END) AS completed_activities,
        COALESCE(ROUND(AVG(p.progress_percentage)), 0) AS average_progress,
        (SELECT COUNT(*) FROM certificates cert WHERE cert.student_id = u.id) AS certificate_count
    FROM users u
    LEFT JOIN student_progress p ON p.student_id = u.id
    WHERE u.UserType = 0
    GROUP BY u.id, u.Firstname, u.Lastname
    ORDER BY u.Firstname, u.Lastname
    "
);

$certificateHistory = mysqli_query(
    $con,
    "
    SELECT
        c.id,
        c.student_id,
        c.course_name,
        c.certificate_title,
        c.certificate_message,
        c.certificate_period,
        c.certificate_type,
        c.cert_code,
        COALESCE(c.issue_date, c.created_at) AS issued_on,
        c.issuer_name,
        CONCAT_WS(' ', u.Firstname, u.Lastname) AS student_name
    FROM certificates c
    LEFT JOIN users u ON u.id = c.student_id
    ORDER BY COALESCE(c.issue_date, c.created_at) DESC, c.id DESC
    LIMIT 12
    "
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Achievements | Admin</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .achievement-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.9fr);
            gap: 23px;
            margin-top: 23px;
        }
        .progress-list {
            display: grid;
            gap: 14px;
        }
        .progress-row {
            padding: 14px 0;
            border-top: 1px solid var(--line);
        }
        .progress-row:first-child {
            border-top: 0;
            padding-top: 0;
        }
        .progress-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 8px;
        }
        .progress-meta strong {
            font-size: 14px;
        }
        .bar {
            width: 100%;
            height: 10px;
            background: #edf1f7;
            border-radius: 999px;
            overflow: hidden;
        }
        .bar i {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #2563eb, #0f9bb5);
        }
        .section-gap {
            margin-top: 23px;
        }
        .cert-form .hint {
            color: var(--muted);
            font-size: 12px;
            margin-top: -6px;
        }
        .history-card {
            display: grid;
            gap: 10px;
        }
        .history-item {
            padding: 12px 0;
            border-top: 1px solid var(--line);
        }
        .history-item:first-child {
            border-top: 0;
            padding-top: 0;
        }
        .history-item small {
            display: block;
            margin-top: 4px;
        }
        @media (max-width: 1100px) {
            .achievement-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="Dashboard.php"><span class="brand-mark">J</span><span>Jidanao <b>LMS</b></span></a>
            <nav>
                <a href="Dashboard.php"><i class="fa-solid fa-grid-2"></i> Overview</a>
                <a href="courses.php"><i class="fa-solid fa-book-open"></i> Courses</a>
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
                    <p class="subtle">Track learner progress and issue downloadable certificates.</p>
                </div>
            </header>

            <?php if ($flash !== ''): ?>
                <div class="flash success" style="margin-bottom: 16px;">
                    <i class="fa-solid fa-circle-check"></i>
                    <?= e($flash) ?>
                </div>
            <?php endif; ?>

            <section class="achievement-grid">
                <article class="panel wide">
                    <div class="panel-title">
                        <h2>Progress Overview</h2>
                        <span class="grade-total"><strong><?= (int) mysqli_num_rows($progressQuery) ?></strong> students</span>
                    </div>

                    <div class="progress-list">
                        <?php while ($row = mysqli_fetch_assoc($progressQuery)):
                            $percent = min(100, $totalActivities > 0 ? ((int) $row['completed_activities'] / $totalActivities) * 100 : 0);
                        ?>
                        <div class="progress-row">
                            <div class="progress-meta">
                                <div>
                                    <strong><?= e($row['Firstname']) ?> <?= e($row['Lastname']) ?></strong>
                                    <small><?= (int) $row['completed_activities'] ?>/<?= $totalActivities ?> activities completed · <?= (int) $row['tracked_activities'] ?> tracked</small>
                                </div>
                                <span class="badge <?= $percent >= 100 ? 'active' : 'archived' ?>"><?= (int) round($percent) ?>%</span>
                            </div>
                            <div class="bar"><i style="width: <?= (int) round($percent) ?>%;"></i></div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </article>

                <article class="panel cert-form">
                    <div class="panel-title">
                        <h2>Issue Certificate</h2>
                    </div>
                    <p class="subtle">Create the title and message that will appear on the certificate, then send it directly to one student.</p>
                    <form method="POST" action="issue_cert.php" class="stack-form" style="margin-top: 18px;">
                        <label>
                            Student
                            <select name="student_id" required>
                                <option value="">Select a student</option>
                                <?php mysqli_data_seek($students, 0); while ($student = mysqli_fetch_assoc($students)): ?>
                                    <option value="<?= (int) $student['id'] ?>"><?= e($student['Firstname'] . ' ' . $student['Lastname']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </label>

                        <label>
                            Course name
                            <select name="course_name" required>
                                <option value="">Select a course</option>
                                <?php if ($courses && mysqli_num_rows($courses) > 0): ?>
                                    <?php while ($course = mysqli_fetch_assoc($courses)): ?>
                                        <option value="<?= e($course['title']) ?>"><?= e($course['course_code'] . ' — ' . $course['title']) ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </label>

                        <label>
                            Certificate title
                            <input type="text" name="certificate_title" placeholder="e.g. CERTIFICATE OF READING EXCELLENCE" required>
                        </label>

                        <label>
                            Certificate message
                            <textarea name="certificate_message" placeholder="Write the recognition text that appears on the certificate."></textarea>
                        </label>

                        <label>
                            Certificate period
                            <input type="text" name="certificate_period" placeholder="e.g. 1st Grading Period">
                        </label>

                        <label>
                            Issuer name
                            <input type="text" name="issuer_name" value="Jidanao LMS Admin" placeholder="Jidanao LMS Admin">
                        </label>

                        <div class="hint">The student will see the certificate in their achievements page and can download it as PDF.</div>
                        <button type="submit" class="primary"><i class="fa-solid fa-award"></i> Issue certificate</button>
                    </form>
                </article>
            </section>

            <section class="panel section-gap">
                <div class="panel-title">
                    <h2>Recent Certificates</h2>
                    <span class="grade-total"><strong><?= (int) mysqli_num_rows($certificateHistory) ?></strong> recent</span>
                </div>
                <div class="history-card">
                    <?php if ($certificateHistory && mysqli_num_rows($certificateHistory) > 0): ?>
                        <?php while ($certificate = mysqli_fetch_assoc($certificateHistory)): ?>
                            <div class="history-item">
                                <strong><?= e($certificate['student_name'] ?: 'Unknown student') ?></strong>
                                <span><?= e($certificate['certificate_title'] ?: $certificate['course_name'] ?: $certificate['certificate_type']) ?></span>
                                <small>Code: <?= e($certificate['cert_code']) ?> · Issued: <?= e(date('M d, Y', strtotime((string) $certificate['issued_on']))) ?> · By <?= e($certificate['issuer_name'] ?: 'Jidanao LMS Admin') ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="subtle">No certificates have been issued yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
