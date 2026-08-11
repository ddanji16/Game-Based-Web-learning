<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/Admin-folder/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['activity_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing student session or activity id.']);
    exit;
}

$studentId = (int) $_SESSION['user_id'];
$activityId = trim((string) $_POST['activity_id']);
$activityTitle = trim((string) ($_POST['activity_title'] ?? $activityId));
$status = in_array(trim((string) ($_POST['status'] ?? 'started')), ['started', 'completed'], true)
    ? trim((string) ($_POST['status'] ?? 'started'))
    : 'started';
$score = max(0, (int) ($_POST['score'] ?? 0));

if ((int) ($_SESSION['usertype'] ?? -1) !== 0 || $studentId <= 0 || $activityId === '' || $activityTitle === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid student or activity data.']);
    exit;
}

$progress = $status === 'completed' ? 100 : 0;
$statement = mysqli_prepare($con, 'SELECT id FROM student_progress WHERE student_id = ? AND activity_title = ? LIMIT 1');
mysqli_stmt_bind_param($statement, 'is', $studentId, $activityTitle);
mysqli_stmt_execute($statement);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));

if ($existing) {
    $statement = mysqli_prepare($con, 'UPDATE student_progress SET activity_title = ?, progress_percentage = ?, score = ?, status = ? WHERE id = ?');
    mysqli_stmt_bind_param($statement, 'sidsi', $activityTitle, $progress, $score, $status, $existing['id']);
} else {
    $statement = mysqli_prepare($con, 'INSERT INTO student_progress (student_id, activity_title, progress_percentage, score, status) VALUES (?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($statement, 'isids', $studentId, $activityTitle, $progress, $score, $status);
}

if (mysqli_stmt_execute($statement)) {
    echo json_encode(['success' => true, 'activity_id' => $activityId, 'status' => $status, 'score' => $score]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not save progress.']);
}
