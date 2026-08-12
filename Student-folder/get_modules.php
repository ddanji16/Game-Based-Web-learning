<?php
require_once __DIR__ . '/../Admin-folder/database.php';

if (!isset($_GET['course_id'])) {
    echo json_encode(['success' => false, 'message' => 'Course ID missing']);
    exit;
}

$courseId = (int)$_GET['course_id'];

// Get the first active module for this course
$query = "SELECT * FROM course_modules WHERE course_id = $courseId ORDER BY order_index ASC LIMIT 1";
$result = mysqli_query($con, $query);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode(['success' => true, 'module' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'No modules found']);
}
?>
