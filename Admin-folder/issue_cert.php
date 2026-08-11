<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/database.php';

if (!isset($_SESSION['usertype']) || (int) $_SESSION['usertype'] !== 1) {
    header('Location: ../Form-folder/login.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: achievements.php');
    exit;
}

$studentId = (int) ($_POST['student_id'] ?? 0);
$courseName = trim((string) ($_POST['course_name'] ?? ''));
$certificateTitle = trim((string) ($_POST['certificate_title'] ?? $courseName));
$certificateMessage = trim((string) ($_POST['certificate_message'] ?? ''));
$certificatePeriod = trim((string) ($_POST['certificate_period'] ?? ''));
$issuerName = trim((string) ($_POST['issuer_name'] ?? 'Jidanao LMS Admin'));

if ($studentId <= 0 || $certificateTitle === '') {
    $_SESSION['flash'] = 'Please choose a student and fill in the certificate title before issuing it.';
    header('Location: achievements.php');
    exit;
}

$certCode = 'CERT-' . strtoupper(bin2hex(random_bytes(4)));
$certificateType = $certificateTitle;
$issuerName = $issuerName !== '' ? $issuerName : 'Jidanao LMS Admin';
$certificateMessage = $certificateMessage !== '' ? $certificateMessage : "This certifies that the student has been recognized for completing {$certificateTitle}.";
$certificatePeriod = $certificatePeriod !== '' ? $certificatePeriod : 'Current term';

$stmt = mysqli_prepare(
    $con,
    "INSERT INTO certificates (student_id, course_id, certificate_type, course_name, certificate_title, certificate_message, certificate_period, cert_code, issuer_name)
     VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, 'isssssss', $studentId, $certificateType, $courseName, $certificateTitle, $certificateMessage, $certificatePeriod, $certCode, $issuerName);

if (mysqli_stmt_execute($stmt)) {
    $title = 'Certificate issued';
    $message = "A certificate for {$certificateTitle} is now ready. Your certificate code is {$certCode}.";
    $notification = mysqli_prepare(
        $con,
        "INSERT INTO notifications (recipient_id, title, message, audience) VALUES (?, ?, ?, 'students')"
    );
    mysqli_stmt_bind_param($notification, 'iss', $studentId, $title, $message);
    mysqli_stmt_execute($notification);

    $_SESSION['flash'] = 'Certificate issued successfully.';
} else {
    $_SESSION['flash'] = 'The certificate could not be issued.';
}

header('Location: achievements.php');
exit;
