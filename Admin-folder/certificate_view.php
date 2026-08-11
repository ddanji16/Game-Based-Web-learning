<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/database.php';

$code = trim((string) ($_GET['code'] ?? ''));

if ($code === '') {
    echo 'Certificate code is missing.';
    exit;
}

$stmt = mysqli_prepare($con, 'SELECT c.id, c.student_id, c.course_id, c.certificate_type, c.cert_code, u.Firstname, u.Lastname, co.title AS course_title FROM certificates c LEFT JOIN users u ON u.id = c.student_id LEFT JOIN courses co ON co.id = c.course_id WHERE c.cert_code = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $code);
mysqli_stmt_execute($stmt);
$certificate = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$certificate) {
    echo 'Certificate not found.';
    exit;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificate Preview</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 40px; }
        .card { max-width: 900px; margin: 0 auto; background: white; border: 12px solid #2563eb; border-radius: 18px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        h1 { text-align: center; color: #1d4ed8; margin-bottom: 20px; }
        .info { text-align: center; font-size: 18px; line-height: 1.8; }
        .badge { display: inline-block; margin-top: 20px; padding: 8px 14px; background: #e8f0ff; color: #1d4ed8; border-radius: 999px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Certificate of Completion</h1>
        <div class="info">
            <p>This certifies that</p>
            <h2><?= e($certificate['Firstname'] . ' ' . $certificate['Lastname']) ?></h2>
            <p>has successfully completed</p>
            <h3><?= e($certificate['certificate_type']) ?></h3>
            <p>for</p>
            <h3><?= e($certificate['course_title'] ?: 'the assigned course') ?></h3>
            <div class="badge">Code: <?= e($certificate['cert_code']) ?></div>
        </div>
    </div>
</body>
</html>
