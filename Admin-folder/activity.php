<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['usertype']) || (int) $_SESSION['usertype'] !== 1) {
    header('Location: ../Form-folder/login.php');
    exit;
}

function a($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function activityBack($type, $message) {
    $_SESSION['activity_flash'] = [$type, $message];
    header('Location: activity.php');
    exit;
}

mysqli_query($con, "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action_text VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        activityBack('error', 'Your session expired. Please try again.');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $statement = mysqli_prepare($con, 'DELETE FROM activity_logs WHERE id = ?');
        mysqli_stmt_bind_param($statement, 'i', $id);
        mysqli_stmt_execute($statement);
        activityBack('success', 'Activity log entry removed.');
    }

    if ($action === 'clear_all') {
        mysqli_query($con, 'DELETE FROM activity_logs');
        activityBack('success', 'All activity logs cleared.');
    }
}

$flash = $_SESSION['activity_flash'] ?? null; unset($_SESSION['activity_flash']);

$total = (int) mysqli_fetch_assoc(mysqli_query($con, 'SELECT COUNT(*) c FROM activity_logs'))['c'];
$today = (int) mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) c FROM activity_logs WHERE DATE(created_at) = CURDATE()"))['c'];

$activities = mysqli_query($con, "SELECT a.id, a.action_text, a.created_at, CONCAT_WS(' ', u.Firstname, u.Lastname) person FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Activity Logs | Admin | Jidanao LMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="activity.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
                <a href="notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
                <a class="active" href="activity.php"><i class="fa-solid fa-clock-rotate-left"></i> Activity logs</a>
            </nav>
            <a class="logout" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a>
        </aside>
        <main>
            <header>
                <button class="menu-btn" id="menuButton" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                <div>
                    <p class="eyebrow">ADMINISTRATOR PORTAL</p>
                    <h1>Activity logs</h1>
                    <p class="subtle">Audit trail of every action taken across the system.</p>
                </div>
            </header>
            <?php if ($flash): ?>
            <div class="flash <?= a($flash[0]) ?>"><i class="fa-solid <?= $flash[0] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i> <?= a($flash[1]) ?></div>
            <?php endif; ?>
            <section class="stats activity-stats">
                <article><span class="stat-icon blue"><i class="fa-solid fa-clock-rotate-left"></i></span>
                    <div><small>Total actions</small><strong><?= $total ?></strong><em>All logged activity</em></div>
                </article>
                <article><span class="stat-icon green"><i class="fa-solid fa-calendar-day"></i></span>
                    <div><small>Today</small><strong><?= $today ?></strong><em>Actions today</em></div>
                </article>
            </section>
            <section class="panel activity-panel">
                <div class="panel-title">
                    <div>
                        <p class="eyebrow">AUDIT TRAIL</p>
                        <h2>All activity</h2>
                    </div>
                    <?php if ($total > 0): ?>
                    <form method="post" onsubmit="return confirm('Clear ALL activity logs?');">
                        <input type="hidden" name="csrf_token" value="<?= a($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="clear_all">
                        <button class="text-button danger-text" title="Clear all"><i class="fa-solid fa-trash-can"></i> Clear all</button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="timeline">
                    <?php if (mysqli_num_rows($activities) === 0): ?>
                    <p class="no-records">No activity logged yet.</p>
                    <?php else: while ($activity = mysqli_fetch_assoc($activities)): ?>
                    <div class="activity-item">
                        <span><i class="fa-solid fa-circle"></i></span>
                        <p><b><?= a($activity['person'] ?: 'System') ?></b> <?= a($activity['action_text']) ?><small><?= a(date('M j, g:i A', strtotime($activity['created_at']))) ?></small></p>
                        <form method="post" onsubmit="return confirm('Delete this log entry?');">
                            <input type="hidden" name="csrf_token" value="<?= a($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$activity['id'] ?>">
                            <button class="icon-button danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                    <?php endwhile; endif; ?>
                </div>
            </section>
        </main>
    </div>
    <script src="dashboard.js"></script>
</body>
</html>
