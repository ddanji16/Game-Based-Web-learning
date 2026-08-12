<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['usertype']) || (int) $_SESSION['usertype'] !== 1) {
    header('Location: ../Form-folder/login.php');
    exit;
}

function n($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function notifBack($type, $message) {
    $_SESSION['notifications_flash'] = [$type, $message];
    header('Location: notifications.php');
    exit;
}

mysqli_query($con, "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    audience ENUM('all','students','teachers') NOT NULL DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        notifBack('error', 'Your session expired. Please try again.');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'post_notification') {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $audience = $_POST['audience'] ?? 'all';
        if ($title === '' || $message === '' || !in_array($audience, ['all','students','teachers'], true)) {
            notifBack('error', 'Provide a title, message, and audience.');
        }
        $statement = mysqli_prepare($con, 'INSERT INTO notifications (title, message, audience) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($statement, 'sss', $title, $message, $audience);
        mysqli_stmt_execute($statement);
        notifBack('success', 'Notification published.');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $statement = mysqli_prepare($con, 'DELETE FROM notifications WHERE id = ?');
        mysqli_stmt_bind_param($statement, 'i', $id);
        mysqli_stmt_execute($statement);
        notifBack('success', 'Notification deleted.');
    }

    if ($action === 'clear_all') {
        mysqli_query($con, 'DELETE FROM notifications');
        notifBack('success', 'All notifications cleared.');
    }
}

$flash = $_SESSION['notifications_flash'] ?? null; unset($_SESSION['notifications_flash']);
$total = (int) mysqli_fetch_assoc(mysqli_query($con, 'SELECT COUNT(*) c FROM notifications'))['c'];
$notifications = mysqli_query($con, "SELECT id, title, message, audience, created_at FROM notifications ORDER BY created_at DESC");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Notifications | Admin | Jidanao LMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="notifications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
                <a class="active" href="notifications.php"><i class="fa-solid fa-bell"></i> Announcement</a>
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
                    <h1>Announcements Notifications</h1>
                    <p class="subtle">Publish updates and manage announcements for your community.</p>
                </div>
            </header>
            <?php if ($flash): ?>
            <div class="flash <?= n($flash[0]) ?>"><i class="fa-solid <?= $flash[0] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i> <?= n($flash[1]) ?></div>
            <?php endif; ?>
            <section class="stats notif-stats">
                <article><span class="stat-icon orange"><i class="fa-solid fa-bullhorn"></i></span>
                    <div><small>Total notifications</small><strong><?= $total ?></strong><em>Published updates</em></div>
                </article>
            </section>
            <section class="notif-grid">
                <article class="panel">
                    <div class="panel-title">
                        <div>
                            <p class="eyebrow">COMMUNICATION</p>
                            <h2>Publish update</h2>
                        </div>
                    </div>
                    <form method="post" class="stack-form">
                        <input type="hidden" name="csrf_token" value="<?= n($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="post_notification">
                        <label>Title<input name="title" maxlength="150" required placeholder="e.g. Quiz schedule update"></label>
                        <label>Message<textarea name="message" required placeholder="Write a useful, clear update..."></textarea></label>
                        <label>Audience<select name="audience">
                            <option value="all">Everyone</option>
                            <option value="students">Guest only</option>
                            <option value="teachers">Teachers only</option>
                        </select></label>
                        <button class="primary" type="submit">Publish notification</button>
                    </form>
                </article>
                <article class="panel">
                    <div class="panel-title">
                        <div>
                            <p class="eyebrow">LATEST NOTICES</p>
                            <h2>All notifications</h2>
                        </div>
                        <?php if ($total > 0): ?>
                        <form method="post" onsubmit="return confirm('Clear ALL notifications?');">
                            <input type="hidden" name="csrf_token" value="<?= n($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="clear_all">
                            <button class="text-button danger-text" title="Clear all"><i class="fa-solid fa-trash-can"></i> Clear all</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="notices">
                        <?php if (mysqli_num_rows($notifications) === 0): ?>
                        <p class="no-records">No notifications yet.</p>
                        <?php else: while ($notification = mysqli_fetch_assoc($notifications)): ?>
                        <article>
                            <span class="notice-icon"><i class="fa-solid fa-bullhorn"></i></span>
                            <div class="notice-body">
                                <b><?= n($notification['title']) ?></b>
                                <p><?= n($notification['message']) ?></p>
                                <small>To <?= n($notification['audience']) ?> · <?= n(date('M j, g:i A', strtotime($notification['created_at']))) ?></small>
                            </div>
                            <form method="post" onsubmit="return confirm('Delete this notification?');">
                                <input type="hidden" name="csrf_token" value="<?= n($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$notification['id'] ?>">
                                <button class="icon-button danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </article>
                        <?php endwhile; endif; ?>
                    </div>
                </article>
            </section>
        </main>
    </div>
    <script src="dashboard.js"></script>
</body>
</html>
