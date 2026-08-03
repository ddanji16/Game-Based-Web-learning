<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['usertype']) || (int) $_SESSION['usertype'] !== 1) {
    header('Location: ../Form-folder/login.php');
    exit;
}

function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function tableExists($connection, $table) {
    $statement = mysqli_prepare($connection, 'SHOW TABLES LIKE ?');
    mysqli_stmt_bind_param($statement, 's', $table);
    mysqli_stmt_execute($statement);
    return mysqli_num_rows(mysqli_stmt_get_result($statement)) > 0;
}
function ensureLmsTables($connection) {
    $queries = [
        "CREATE TABLE IF NOT EXISTS courses (
            id INT AUTO_INCREMENT PRIMARY KEY, course_code VARCHAR(30) NOT NULL UNIQUE,
            title VARCHAR(150) NOT NULL, description TEXT NULL, teacher_id INT NULL,
            status ENUM('active','archived') NOT NULL DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_course_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, student_id INT NOT NULL,
            enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY unique_enrollment (course_id, student_id),
            CONSTRAINT fk_enrollment_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            CONSTRAINT fk_enrollment_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(150) NOT NULL, message TEXT NOT NULL,
            audience ENUM('all','students','teachers') NOT NULL DEFAULT 'all', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, action_text VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    foreach ($queries as $query) { mysqli_query($connection, $query); }
}
function logActivity($connection, $userId, $message) {
    $statement = mysqli_prepare($connection, 'INSERT INTO activity_logs (user_id, action_text) VALUES (?, ?)');
    mysqli_stmt_bind_param($statement, 'is', $userId, $message);
    mysqli_stmt_execute($statement);
}
function redirectWithMessage($type, $message) {
    $_SESSION['dashboard_flash'] = [$type, $message];
    header('Location: Dashboard.php');
    exit;
}

ensureLmsTables($con);
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$adminId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        redirectWithMessage('error', 'Your session expired. Please try again.');
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'create_course') {
        $code = strtoupper(trim($_POST['course_code'] ?? ''));
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        if ($code === '' || $title === '') { redirectWithMessage('error', 'Course code and title are required.'); }
        $teacherId = $teacherId ?: null;
        $statement = mysqli_prepare($con, 'INSERT INTO courses (course_code, title, description, teacher_id) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($statement, 'sssi', $code, $title, $description, $teacherId);
        if (!mysqli_stmt_execute($statement)) { redirectWithMessage('error', 'Could not create the course. The course code may already exist.'); }
        logActivity($con, $adminId, "Created course: $code — $title");
        $notice = mysqli_prepare($con, "INSERT INTO notifications (title, message, audience) VALUES (?, ?, 'all')");
        $noticeTitle = 'New course available'; $noticeMessage = "$code — $title is now available in the LMS.";
        mysqli_stmt_bind_param($notice, 'ss', $noticeTitle, $noticeMessage); mysqli_stmt_execute($notice);
        redirectWithMessage('success', 'Course created and a notification was posted.');
    }
    if ($action === 'archive_course') {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $statement = mysqli_prepare($con, "UPDATE courses SET status = IF(status = 'active', 'archived', 'active') WHERE id = ?");
        mysqli_stmt_bind_param($statement, 'i', $courseId); mysqli_stmt_execute($statement);
        logActivity($con, $adminId, 'Changed a course status.');
        redirectWithMessage('success', 'Course status updated.');
    }
    if ($action === 'enroll_student') {
        $courseId = (int) ($_POST['course_id'] ?? 0); $studentId = (int) ($_POST['student_id'] ?? 0);
        $statement = mysqli_prepare($con, 'INSERT INTO enrollments (course_id, student_id) VALUES (?, ?)');
        mysqli_stmt_bind_param($statement, 'ii', $courseId, $studentId);
        if (!mysqli_stmt_execute($statement)) { redirectWithMessage('error', 'This student is already enrolled in that course.'); }
        logActivity($con, $adminId, 'Enrolled a student in a course.');
        redirectWithMessage('success', 'Student enrolled successfully.');
    }
    if ($action === 'update_role') {
        $userId = (int) ($_POST['user_id'] ?? 0); $role = (int) ($_POST['usertype'] ?? -1);
        if (!in_array($role, [0, 1, 2], true) || $userId === $adminId) { redirectWithMessage('error', 'That role update is not allowed.'); }
        $statement = mysqli_prepare($con, 'UPDATE users SET UserType = ? WHERE id = ?');
        mysqli_stmt_bind_param($statement, 'ii', $role, $userId); mysqli_stmt_execute($statement);
        logActivity($con, $adminId, 'Updated a user role.');
        redirectWithMessage('success', 'User role updated.');
    }
    if ($action === 'post_notification') {
        $title = trim($_POST['title'] ?? ''); $message = trim($_POST['message'] ?? ''); $audience = $_POST['audience'] ?? 'all';
        if ($title === '' || $message === '' || !in_array($audience, ['all','students','teachers'], true)) { redirectWithMessage('error', 'Provide a title, message, and audience.'); }
        $statement = mysqli_prepare($con, 'INSERT INTO notifications (title, message, audience) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($statement, 'sss', $title, $message, $audience); mysqli_stmt_execute($statement);
        logActivity($con, $adminId, "Posted notification: $title");
        redirectWithMessage('success', 'Notification published.');
    }
}

$flash = $_SESSION['dashboard_flash'] ?? null; unset($_SESSION['dashboard_flash']);
$stats = mysqli_fetch_assoc(mysqli_query($con, "SELECT (SELECT COUNT(*) FROM users WHERE UserType = 0) students, (SELECT COUNT(*) FROM users WHERE UserType = 2) teachers, (SELECT COUNT(*) FROM courses WHERE status = 'active') courses, (SELECT COUNT(*) FROM enrollments) enrollments"));
$courses = mysqli_query($con, "SELECT c.*, CONCAT_WS(' ', u.Firstname, u.Lastname) teacher, COUNT(e.id) enrollment_count FROM courses c LEFT JOIN users u ON u.id = c.teacher_id LEFT JOIN enrollments e ON e.course_id = c.id GROUP BY c.id ORDER BY c.created_at DESC");
$users = mysqli_query($con, "SELECT id, Firstname, Lastname, Email, UserType FROM users ORDER BY Firstname, Lastname");
$teachers = mysqli_query($con, "SELECT id, Firstname, Lastname FROM users WHERE UserType = 2 ORDER BY Firstname, Lastname");
$students = mysqli_query($con, "SELECT id, Firstname, Lastname FROM users WHERE UserType = 0 ORDER BY Firstname, Lastname");
$activeCourses = mysqli_query($con, "SELECT id, course_code, title FROM courses WHERE status = 'active' ORDER BY title");
$activities = mysqli_query($con, "SELECT a.action_text, a.created_at, CONCAT_WS(' ', u.Firstname, u.Lastname) person FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT 6");
$notifications = mysqli_query($con, "SELECT title, message, audience, created_at FROM notifications ORDER BY created_at DESC LIMIT 4");
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard | Jidanao LMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar"><a class="brand" href="#overview"><span
                    class="brand-mark">J</span><span>Jidanao <b>LMS</b></span></a>
            <nav><a class="active" href="#overview"><i class="fa-solid fa-grid-2"></i> Overview</a><a href="#courses"><i
                        class="fa-solid fa-book-open"></i> Courses</a><a href="#people"><i
                        class="fa-solid fa-users"></i> Users</a><a href="#enrollments"><i
                        class="fa-solid fa-user-plus"></i> Enrollments</a><a href="#notifications"><i
                        class="fa-solid fa-bell"></i> Notifications</a><a href="#activity"><i
                        class="fa-solid fa-clock-rotate-left"></i> Activity logs</a></nav><a class="logout"
                href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a>
        </aside>
        <main>
            <header><button class="menu-btn" id="menuButton" aria-label="Open menu"><i
                        class="fa-solid fa-bars"></i></button>
                <div>
                    <p class="eyebrow">ADMINISTRATOR PORTAL</p>
                    <h1 id="overview">Good day,
                        <?= e($_SESSION['firstname'] ?? 'Administrator') ?>.
                    </h1>
                    <p class="subtle">Here is a clear view of your learning community.</p>
                </div><button class="primary" data-modal="courseModal"><i class="fa-solid fa-plus"></i> Create
                    course</button>
            </header>
            <?php if ($flash): ?>
            <div class="flash <?= e($flash[0]) ?>"><i
                    class="fa-solid <?= $flash[0] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($flash[1]) ?>
            </div>
            <?php endif; ?>
            <section class="stats">
                <article><span class="stat-icon blue"><i class="fa-solid fa-user-graduate"></i></span>
                    <div><small>Students</small><strong>
                            <?= (int)$stats['students'] ?>
                        </strong><em>Registered learners</em></div>
                </article>
                <article><span class="stat-icon purple"><i class="fa-solid fa-chalkboard-user"></i></span>
                    <div><small>Teachers</small><strong>
                            <?= (int)$stats['teachers'] ?>
                        </strong><em>Teaching staff</em></div>
                </article>
                <article><span class="stat-icon orange"><i class="fa-solid fa-book"></i></span>
                    <div><small>Active courses</small><strong>
                            <?= (int)$stats['courses'] ?>
                        </strong><em>Available to learn</em></div>
                </article>
                <article><span class="stat-icon green"><i class="fa-solid fa-link"></i></span>
                    <div><small>Enrollments</small><strong>
                            <?= (int)$stats['enrollments'] ?>
                        </strong><em>Course registrations</em></div>
                </article>
            </section>
            <section class="grid">
                <article class="panel wide" id="courses">
                    <div class="panel-title">
                        <div>
                            <p class="eyebrow">COURSE MANAGEMENT</p>
                            <h2>Courses and programs</h2>
                        </div><button class="text-button" data-modal="courseModal">Add course <i
                                class="fa-solid fa-arrow-right"></i></button>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Teacher</th>
                                    <th>Enrolled</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($course = mysqli_fetch_assoc($courses)): ?>
                                <tr>
                                    <td><b>
                                            <?= e($course['course_code']) ?>
                                        </b><span>
                                            <?= e($course['title']) ?>
                                        </span></td>
                                    <td>
                                        <?= e($course['teacher'] ?: 'Unassigned') ?>
                                    </td>
                                    <td>
                                        <?= (int)$course['enrollment_count'] ?> learners
                                    </td>
                                    <td><span class="badge <?= e($course['status']) ?>">
                                            <?= e(ucfirst($course['status'])) ?>
                                        </span></td>
                                    <td>
                                        <form method="post"><input type="hidden" name="csrf_token"
                                                value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden"
                                                name="action" value="archive_course"><input type="hidden"
                                                name="course_id" value="<?= (int)$course['id'] ?>"><button
                                                class="icon-button" title="Change course status"><i
                                                    class="fa-solid fa-arrows-rotate"></i></button></form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
                <article class="panel" id="activity">
                    <div class="panel-title">
                        <div>
                            <p class="eyebrow">AUDIT TRAIL</p>
                            <h2>Recent activity</h2>
                        </div>
                    </div>
                    <div class="timeline">
                        <?php while ($activity = mysqli_fetch_assoc($activities)): ?>
                        <div><span><i class="fa-solid fa-circle"></i></span>
                            <p><b>
                                    <?= e($activity['person'] ?: 'System') ?>
                                </b>
                                <?= e($activity['action_text']) ?><small>
                                    <?= e(date('M j, g:i A', strtotime($activity['created_at']))) ?>
                                </small>
                            </p>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </article>
            </section>
            <section class="grid lower">
                <article class="panel" id="enrollments">
                    <div class="panel-title">
                        <div>
                            <p class="eyebrow">LEARNING ACCESS</p>
                            <h2>Enroll a student</h2>
                        </div>
                    </div>
                    <form method="post" class="stack-form"><input type="hidden" name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action"
                            value="enroll_student"><label>Student<select name="student_id" required>
                                <option value="">Choose a student</option>
                                <?php while ($student = mysqli_fetch_assoc($students)): ?>
                                <option value="<?= (int)$student['id'] ?>">
                                    <?= e($student['Firstname'].' '.$student['Lastname']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select></label><label>Active course<select name="course_id" required>
                                <option value="">Choose a course</option>
                                <?php while ($activeCourse = mysqli_fetch_assoc($activeCourses)): ?>
                                <option value="<?= (int)$activeCourse['id'] ?>">
                                    <?= e($activeCourse['course_code'].' — '.$activeCourse['title']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select></label><button class="primary" type="submit">Enroll student</button></form>
                </article>
                <article class="panel" id="notifications">
                    <div class="panel-title">
                        <div>
                            <p class="eyebrow">COMMUNICATION</p>
                            <h2>Publish update</h2>
                        </div>
                    </div>
                    <form method="post" class="stack-form"><input type="hidden" name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action"
                            value="post_notification"><label>Title<input name="title" maxlength="150" required
                                placeholder="e.g. Quiz schedule update"></label><label>Message<textarea name="message"
                                required
                                placeholder="Write a useful, clear update..."></textarea></label><label>Audience<select
                                name="audience">
                                <option value="all">Everyone</option>
                                <option value="students">Students only</option>
                                <option value="teachers">Teachers only</option>
                            </select></label><button class="primary" type="submit">Publish notification</button></form>
                </article>
                <article class="panel" id="people">
                    <div class="panel-title">
                        <div>
                            <p class="eyebrow">USER MANAGEMENT</p>
                            <h2>People and roles</h2>
                        </div>
                    </div>
                    <div class="people-list">
                        <?php while ($user = mysqli_fetch_assoc($users)): ?>
                        <form method="post" class="person"><input type="hidden" name="csrf_token"
                                value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action"
                                value="update_role"><input type="hidden" name="user_id"
                                value="<?= (int)$user['id'] ?>"><span class="avatar">
                                <?= e(strtoupper(substr($user['Firstname'], 0, 1))) ?>
                            </span><span><b>
                                    <?= e($user['Firstname'].' '.$user['Lastname']) ?>
                                </b><small>
                                    <?= e($user['Email']) ?>
                                </small></span><select name="usertype" onchange="this.form.submit()"
                                <?=(int)$user['id']===$adminId ? 'disabled' : '' ?>><option value="0"
                                    <?=(int)$user['UserType']===0 ? 'selected' : '' ?>>Student</option>
                                <option value="2" <?=(int)$user['UserType']===2 ? 'selected' : '' ?>>Teacher</option>
                                <option value="1" <?=(int)$user['UserType']===1 ? 'selected' : '' ?>>Admin</option>
                            </select></form>
                        <?php endwhile; ?>
                    </div>
                </article>
            </section>
            <section class="panel notices">
                <div class="panel-title">
                    <div>
                        <p class="eyebrow">LATEST NOTICES</p>
                        <h2>Notifications</h2>
                    </div>
                </div>
                <?php while ($notification = mysqli_fetch_assoc($notifications)): ?>
                <article><span class="notice-icon"><i class="fa-solid fa-bullhorn"></i></span>
                    <div><b>
                            <?= e($notification['title']) ?>
                        </b>
                        <p>
                            <?= e($notification['message']) ?>
                        </p><small>To
                            <?= e($notification['audience']) ?> ·
                            <?= e(date('M j', strtotime($notification['created_at']))) ?>
                        </small>
                    </div>
                </article>
                <?php endwhile; ?>
            </section>
        </main>
    </div>
    <div class="modal" id="courseModal" aria-hidden="true">
        <div class="modal-card"><button class="close" data-close aria-label="Close">×</button>
            <p class="eyebrow">COURSE MANAGEMENT</p>
            <h2>Create a course</h2>
            <p class="subtle">Publish a new learning space and notify your community.</p>
            <form method="post" class="stack-form"><input type="hidden" name="csrf_token"
                    value="<?= e($_SESSION['csrf_token']) ?>"><input type="hidden" name="action"
                    value="create_course"><label>Course code<input name="course_code" maxlength="30" required
                        placeholder="e.g. MATH-101"></label><label>Course title<input name="title" maxlength="150"
                        required placeholder="e.g. Foundations of Mathematics"></label><label>Teacher<select
                        name="teacher_id">
                        <option value="">Assign later</option>
                        <?php while ($teacher = mysqli_fetch_assoc($teachers)): ?>
                        <option value="<?= (int)$teacher['id'] ?>">
                            <?= e($teacher['Firstname'].' '.$teacher['Lastname']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select></label><label>Description<textarea name="description"
                        placeholder="What will learners gain from this course?"></textarea></label><button
                    class="primary" type="submit">Create and notify</button></form>
        </div>
    </div>
    <script src="dashboard.js"></script>
</body>

</html>