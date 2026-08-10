<?php

$server = "localhost";
$username = "root";
$password = "";
$db_name = "schooldb";
$con = "";

$con = mysqli_connect($server, $username, $password, $db_name);

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_query($con, "CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NULL,
    certificate_type VARCHAR(100) NOT NULL,
    cert_code VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($con, "ALTER TABLE certificates ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

?>