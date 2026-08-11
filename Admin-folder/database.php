<?php
$server = "localhost";
$username = "root";
$password = "";
$db_name = "schooldb";
$con = mysqli_connect($server, $username, $password, $db_name);

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

$tables = [
    "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_code VARCHAR(30) NOT NULL UNIQUE,
        title VARCHAR(150) NOT NULL,
        description TEXT NULL,
        teacher_id INT NULL,
        status ENUM('active','archived') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_course_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        student_id INT NOT NULL,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_enrollment (course_id, student_id),
        CONSTRAINT fk_enrollment_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        CONSTRAINT fk_enrollment_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NULL,
        title VARCHAR(150) NOT NULL,
        quarter INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_tasks_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS student_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        task_id INT NULL,
        activity_title VARCHAR(150) NOT NULL,
        progress_percentage TINYINT UNSIGNED NOT NULL DEFAULT 0,
        score DECIMAL(5,2) NULL,
        status ENUM('not_started', 'in_progress', 'completed') NOT NULL DEFAULT 'not_started',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_student_progress_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        course_id INT NULL,
        certificate_type VARCHAR(100) NOT NULL,
        cert_code VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient_id INT NULL,
        title VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        audience ENUM('all','students','teachers') NOT NULL DEFAULT 'all',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($tables as $query) {
    mysqli_query($con, $query);
}

mysqli_query($con, "ALTER TABLE student_progress ADD COLUMN IF NOT EXISTS task_id INT NULL");
mysqli_query($con, "ALTER TABLE student_progress ADD COLUMN IF NOT EXISTS activity_title VARCHAR(150) NOT NULL DEFAULT ''");
mysqli_query($con, "ALTER TABLE student_progress ADD COLUMN IF NOT EXISTS progress_percentage TINYINT UNSIGNED NOT NULL DEFAULT 0");
mysqli_query($con, "ALTER TABLE student_progress ADD COLUMN IF NOT EXISTS score DECIMAL(5,2) NULL");
mysqli_query($con, "ALTER TABLE student_progress ADD COLUMN IF NOT EXISTS status ENUM('not_started', 'in_progress', 'completed') NOT NULL DEFAULT 'not_started'");
mysqli_query($con, "ALTER TABLE student_progress ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
mysqli_query($con, "ALTER TABLE certificates ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
mysqli_query($con, "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS recipient_id INT NULL");
?>
