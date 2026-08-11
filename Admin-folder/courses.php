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

function ensureCourseTables($connection)
{
    mysqli_query($connection, "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_code VARCHAR(30) NOT NULL UNIQUE,
        title VARCHAR(150) NOT NULL,
        description TEXT NULL,
        teacher_id INT NULL,
        status ENUM('active','archived') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_course_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query($connection, "CREATE TABLE IF NOT EXISTS enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        student_id INT NOT NULL,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_enrollment (course_id, student_id),
        CONSTRAINT fk_enrollment_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        CONSTRAINT fk_enrollment_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

ensureCourseTables($con);

if (empty($_SESSION['course_csrf_token'])) {
    $_SESSION['course_csrf_token'] = bin2hex(random_bytes(32));
}

$flash = $_SESSION['course_flash'] ?? null;
unset($_SESSION['course_flash']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['course_csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['course_flash'] = ['error', 'Your session expired. Please try again.'];
        header('Location: courses.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_course') {
        $code = strtoupper(trim((string) ($_POST['course_code'] ?? '')));
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'archived' ? 'archived' : 'active';

        if ($code === '' || $title === '') {
            $_SESSION['course_flash'] = ['error', 'Course code and title are required.'];
            header('Location: courses.php');
            exit;
        }

        $statement = mysqli_prepare(
            $con,
            "INSERT INTO courses (course_code, title, description, teacher_id, status)
             VALUES (?, ?, ?, NULLIF(?, 0), ?)"
        );
        mysqli_stmt_bind_param($statement, 'sssis', $code, $title, $description, $teacherId, $status);

        if (!mysqli_stmt_execute($statement)) {
            $_SESSION['course_flash'] = ['error', 'That course code already exists, or the course could not be created.'];
            header('Location: courses.php');
            exit;
        }

        if ($status === 'active') {
            $notice = mysqli_prepare(
                $con,
                "INSERT INTO notifications (recipient_id, title, message, audience) VALUES (NULL, ?, ?, 'all')"
            );
            $noticeTitle = 'New course available';
            $noticeMessage = $title . ' is now live on the student homepage.';
            mysqli_stmt_bind_param($notice, 'ss', $noticeTitle, $noticeMessage);
            mysqli_stmt_execute($notice);
        }

        $_SESSION['course_flash'] = $status === 'active'
            ? ['success', 'Course created and published to the student homepage.']
            : ['success', 'Course created and saved as archived.'];
        header('Location: courses.php');
        exit;
    }

    if ($action === 'toggle_status') {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $currentStatus = ($_POST['current_status'] ?? 'active') === 'active' ? 'active' : 'archived';
        $nextStatus = $currentStatus === 'active' ? 'archived' : 'active';

        $statement = mysqli_prepare($con, 'UPDATE courses SET status = ? WHERE id = ?');
        mysqli_stmt_bind_param($statement, 'si', $nextStatus, $courseId);
        mysqli_stmt_execute($statement);

        $_SESSION['course_flash'] = ['success', 'Course status updated.'];
        header('Location: courses.php');
        exit;
    }
}

$teachers = mysqli_query($con, "SELECT id, Firstname, Lastname FROM users WHERE UserType = 2 ORDER BY Firstname, Lastname");
$courses = mysqli_query(
    $con,
    "SELECT
        c.id,
        c.course_code,
        c.title,
        c.description,
        c.status,
        c.created_at,
        CONCAT_WS(' ', u.Firstname, u.Lastname) AS teacher,
        COUNT(DISTINCT e.id) AS enrollment_count
    FROM courses c
    LEFT JOIN users u ON u.id = c.teacher_id
    LEFT JOIN enrollments e ON e.course_id = c.id
    GROUP BY c.id, c.course_code, c.title, c.description, c.status, c.created_at, u.Firstname, u.Lastname
    ORDER BY c.created_at DESC"
);

$stats = mysqli_fetch_assoc(mysqli_query(
    $con,
    "SELECT
        (SELECT COUNT(*) FROM courses WHERE status = 'active') AS active_courses,
        (SELECT COUNT(*) FROM courses WHERE status = 'archived') AS archived_courses,
        (SELECT COUNT(*) FROM enrollments) AS enrollments"
));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <title>Courses | Admin</title>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="Dashboard.php"><span class="brand-mark">J</span><span>Jidanao <b>LMS</b></span></a>
            <nav>
                <a href="Dashboard.php"><i class="fa-solid fa-grid-2"></i> Overview</a>
                <a class="active" href="courses.php"><i class="fa-solid fa-book-open"></i> Courses</a>
                <a href="users.php"><i class="fa-solid fa-users"></i> Users</a>
                <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
                <a href="notifications.php"><i class="fa-solid fa-bell"></i> Announcement</a>
                <a href="achievements.php"><i class="fa-solid fa-trophy"></i> Achievements</a>
                <a href="activity.php"><i class="fa-solid fa-clock-rotate-left"></i> Activity logs</a>
            </nav>
            <a class="logout" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a>
        </aside>
        <main>
            <header>
                <button class="menu-btn" id="menuButton" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                <div>
                    <p class="eyebrow">ADMINISTRATOR PORTAL</p>
                    <h1>Course Management</h1>
                    <p class="subtle">Create courses here and they will appear on the student homepage as active learning spaces.</p>
                </div>
                <button class="primary" onclick="document.getElementById('course-form').scrollIntoView({behavior:'smooth'})">
                    <i class="fa-solid fa-plus"></i> New course
                </button>
            </header>

            <?php if ($flash): ?>
            <div class="flash <?= e($flash[0]) ?>">
                <i class="fa-solid <?= $flash[0] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($flash[1]) ?>
            </div>
            <?php endif; ?>

            <section class="stats">
                <article>
                    <span class="stat-icon blue"><i class="fa-solid fa-book-open"></i></span>
                    <div>
                        <small>Active courses</small>
                        <strong><?= (int) ($stats['active_courses'] ?? 0) ?></strong>
                        <em>Visible to students</em>
                    </div>
                </article>
                <article>
                    <span class="stat-icon orange"><i class="fa-solid fa-archive"></i></span>
                    <div>
                        <small>Archived</small>
                        <strong><?= (int) ($stats['archived_courses'] ?? 0) ?></strong>
                        <em>Hidden until republished</em>
                    </div>
                </article>
                <article>
                    <span class="stat-icon green"><i class="fa-solid fa-link"></i></span>
                    <div>
                        <small>Total enrollments</small>
                        <strong><?= (int) ($stats['enrollments'] ?? 0) ?></strong>
                        <em>Registered course links</em>
                    </div>
                </article>
                <article>
                    <span class="stat-icon purple"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                    <div>
                        <small>Publish flow</small>
                        <strong>Instant</strong>
                        <em>Active courses show on the homepage</em>
                    </div>
                </article>
            </section>

            <section class="grid">
                <article class="panel wide">
                    <div class="panel-title">
                        <h2>Published Courses</h2>
                        <span class="grade-total"><strong><?= (int) ($stats['active_courses'] ?? 0) ?></strong> live</span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Title</th>
                                    <th>Teacher</th>
                                    <th>Status</th>
                                    <th>Enrollments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($courses && mysqli_num_rows($courses) > 0): ?>
                                    <?php while ($course = mysqli_fetch_assoc($courses)): ?>
                                    <tr>
                                        <td>
                                            <b><?= e($course['course_code']) ?></b>
                                            <span><?= e(date('M d, Y', strtotime($course['created_at']))) ?></span>
                                        </td>
                                        <td>
                                            <b><?= e($course['title']) ?></b>
                                            <span><?= e($course['description'] ?: 'No description yet.') ?></span>
                                        </td>
                                        <td><?= e($course['teacher'] ?: 'Unassigned') ?></td>
                                        <td><span class="badge <?= e($course['status']) ?>"><?= e(ucfirst($course['status'])) ?></span></td>
                                        <td><?= (int) $course['enrollment_count'] ?></td>
                                        <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                            <form method="post" style="margin:0;">
                                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['course_csrf_token']) ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="course_id" value="<?= (int) $course['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= e($course['status']) ?>">
                                                <button class="icon-button" type="submit" title="Toggle status">
                                                    <i class="fa-solid fa-repeat"></i>
                                                </button>
                                            </form>
                                            <a class="icon-button" href="../index.php#courses" title="View on student homepage" style="display:grid;place-items:center;text-decoration:none;">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">
                                            <b>No courses yet</b>
                                            <span>Create the first course using the form on the right.</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="panel" id="course-form">
                    <div class="panel-title">
                        <h2>Create a course</h2>
                    </div>
                    <p class="subtle" style="margin-bottom: 18px;">Active courses are published to the student homepage immediately after you click save.</p>
                    <form method="post" class="stack-form">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['course_csrf_token']) ?>">
                        <input type="hidden" name="action" value="create_course">

                        <label>
                            Course code
                            <input type="text" name="course_code" maxlength="30" placeholder="e.g. ENG-101" required>
                        </label>

                        <label>
                            Title
                            <input type="text" name="title" maxlength="150" placeholder="e.g. Reading Adventures" required>
                        </label>

                        <label>
                            Teacher
                            <select name="teacher_id">
                                <option value="0">Unassigned</option>
                                <?php if ($teachers && mysqli_num_rows($teachers) > 0): ?>
                                    <?php while ($teacher = mysqli_fetch_assoc($teachers)): ?>
                                        <option value="<?= (int) $teacher['id'] ?>"><?= e($teacher['Firstname'] . ' ' . $teacher['Lastname']) ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </label>

                        <label>
                            Description
                            <textarea name="description" placeholder="Describe what students will learn in this course."></textarea>
                        </label>

                        <label>
                            Publish status
                            <select name="status">
                                <option value="active" selected>Active - show on student homepage</option>
                                <option value="archived">Archived - keep hidden for now</option>
                            </select>
                        </label>

                        <button type="submit" class="primary">Save course</button>
                    </form>
                </article>
            </section>
        </main>
    </div>

    <script src="dashboard.js"></script>
</body>
</html>
