<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/database.php';

if (!isset($_SESSION['usertype']) || (int) $_SESSION['usertype'] !== 1) {
    header('Location: ../Form-folder/login.php');
    exit;
}

function m($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }

mysqli_query($con, "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['messages_flash'] = ['error', 'Your session expired. Please try again.'];
        header('Location: messages.php');
        exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_read') {
        $id = (int) ($_POST['id'] ?? 0);
        $statement = mysqli_prepare($con, 'UPDATE contact_messages SET is_read = IF(is_read = 1, 0, 1) WHERE id = ?');
        mysqli_stmt_bind_param($statement, 'i', $id);
        mysqli_stmt_execute($statement);
        $_SESSION['messages_flash'] = ['success', 'Message status updated.'];
        header('Location: messages.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $statement = mysqli_prepare($con, 'DELETE FROM contact_messages WHERE id = ?');
        mysqli_stmt_bind_param($statement, 'i', $id);
        mysqli_stmt_execute($statement);
        $_SESSION['messages_flash'] = ['success', 'Message deleted.'];
        header('Location: messages.php');
        exit;
    }
}

$flash = $_SESSION['messages_flash'] ?? null; unset($_SESSION['messages_flash']);

$total = (int) mysqli_fetch_assoc(mysqli_query($con, 'SELECT COUNT(*) c FROM contact_messages'))['c'];
$unread = (int) mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) c FROM contact_messages WHERE is_read = 0"))['c'];

$messages = mysqli_query($con, "SELECT id, name, email, subject, message, is_read, created_at FROM contact_messages ORDER BY created_at DESC");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Messages | Admin | Jidanao LMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="messages.css">
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
                <a class="active" href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
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
                    <h1>Students Messages</h1>
                    <p class="subtle">Contact messages sent from the home page.</p>
                </div>
            </header>
            <?php if ($flash): ?>
            <div class="flash <?= m($flash[0]) ?>"><i class="fa-solid <?= $flash[0] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i> <?= m($flash[1]) ?></div>
            <?php endif; ?>
            <section class="stats inbox-stats">
                <article><span class="stat-icon blue"><i class="fa-solid fa-inbox"></i></span>
                    <div><small>Total messages</small><strong><?= $total ?></strong><em>All submissions</em></div>
                </article>
                <article><span class="stat-icon orange"><i class="fa-solid fa-envelope-open-text"></i></span>
                    <div><small>Unread</small><strong><?= $unread ?></strong><em>Needs attention</em></div>
                </article>
            </section>
            <section class="panel inbox-panel">
                <div class="panel-title">
                    <div>
                        <p class="eyebrow">CONTACT INBOX</p>
                        <h2>Home page messages</h2>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Sender</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Received</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($messages) === 0): ?>
                            <tr><td colspan="6" class="no-records">No contact messages yet.</td></tr>
                            <?php else: while ($msg = mysqli_fetch_assoc($messages)): ?>
                            <tr class="<?= (int)$msg['is_read'] === 0 ? 'unread' : '' ?>">
                                <td><b><?= m($msg['name']) ?></b><span><?= m($msg['email']) ?></span></td>
                                <td><?= m($msg['subject'] ?: '—') ?></td>
                                <td class="msg-preview"><?= m($msg['message']) ?></td>
                                <td><?= m(date('M j, g:i A', strtotime($msg['created_at']))) ?></td>
                                <td><span class="badge <?= (int)$msg['is_read'] === 0 ? 'active' : 'archived' ?>"><?= (int)$msg['is_read'] === 0 ? 'Unread' : 'Read' ?></span></td>
                                <td class="actions">
                                    <form method="post"><input type="hidden" name="csrf_token" value="<?= m($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="toggle_read"><input type="hidden" name="id" value="<?= (int)$msg['id'] ?>"><button class="icon-button" title="Toggle read/unread"><i class="fa-solid <?= (int)$msg['is_read'] === 0 ? 'fa-envelope-open' : 'fa-envelope' ?>"></i></button></form>
                                    <form method="post" onsubmit="return confirm('Delete this message?');"><input type="hidden" name="csrf_token" value="<?= m($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$msg['id'] ?>"><button class="icon-button danger" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
                                </td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    <script src="dashboard.js"></script>
</body>
</html>
