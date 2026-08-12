<?php
session_start();
require_once __DIR__ . '/../Admin-folder/database.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['course_id'])) {
    header('Location: index.php');
    exit;
}

$studentId = $_SESSION['user_id'];
$courseId = (int)$_POST['course_id'];

$stmt = mysqli_prepare($con, "INSERT IGNORE INTO enrollments (course_id, student_id) VALUES (?, ?)");
mysqli_stmt_bind_param($stmt, 'ii', $courseId, $studentId);

if (mysqli_stmt_execute($stmt)) {
    header('Location: index.php?enrolled=success');
} else {
    header('Location: index.php?enrolled=error');
}
exit;
?>
