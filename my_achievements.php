<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/Admin-folder/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['usertype']) || (int) $_SESSION['usertype'] !== 0) {
    header('Location: Form-folder/login.php');
    exit;
}

function myA($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$studentId = (int) $_SESSION['user_id'];
$studentName = trim((string) ($_SESSION['firstname'] ?? 'Student'));

$certificates = [];
$statement = mysqli_prepare(
    $con,
    "SELECT
        id,
        student_id,
        COALESCE(NULLIF(course_name, ''), NULLIF(certificate_type, ''), 'Certificate') AS course_name,
        certificate_type,
        cert_code,
        COALESCE(issue_date, created_at) AS issued_on,
        issuer_name
     FROM certificates
     WHERE student_id = ?
     ORDER BY COALESCE(issue_date, created_at) DESC, id DESC"
);
mysqli_stmt_bind_param($statement, 'i', $studentId);
mysqli_stmt_execute($statement);
$certificates = mysqli_fetch_all(mysqli_stmt_get_result($statement), MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Achievements | Jidanao LMS</title>
    <link rel="stylesheet" href="account.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        .achievement-wrap { max-width: 1180px; margin: 0 auto; padding: 34px 24px 60px; }
        .achievement-hero {
            display: flex; align-items: center; justify-content: space-between; gap: 18px;
            padding: 24px; border-radius: 18px; color: #fff;
            background: linear-gradient(135deg, #2563eb, #0f9bb5);
            box-shadow: 0 14px 30px #2462db2e;
        }
        .achievement-hero h1 { margin: 4px 0; font-size: clamp(28px, 3vw, 40px); }
        .achievement-hero p { margin: 0; opacity: .92; }
        .achievement-hero a {
            background: #fff; color: #2563eb; text-decoration: none; font-weight: 800;
            padding: 10px 14px; border-radius: 10px; white-space: nowrap;
        }
        .cert-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; margin-top: 18px; }
        .cert-card {
            background: #fff; border: 1px solid #e7ebf4; border-radius: 16px; padding: 20px;
            box-shadow: 0 5px 18px #20365b08;
        }
        .cert-card h3 { margin: 0 0 8px; font-size: 20px; }
        .cert-meta { color: #667085; font-size: 13px; display: grid; gap: 4px; margin-bottom: 16px; }
        .cert-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .primary {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            border: 0; border-radius: 10px; background: #2563eb; color: #fff;
            padding: 10px 14px; font: inherit; font-weight: 800; cursor: pointer;
        }
        .secondary {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            border: 1px solid #d7deea; border-radius: 10px; background: #fff; color: #17213a;
            padding: 10px 14px; font: inherit; font-weight: 800; cursor: pointer; text-decoration: none;
        }
        .empty {
            margin-top: 18px; padding: 26px; border-radius: 16px; border: 1px dashed #d7deea;
            background: #fff; color: #667085; text-align: center;
        }
        @media (max-width: 800px) {
            .achievement-hero { flex-direction: column; align-items: flex-start; }
            .cert-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="achievement-wrap">
        <section class="achievement-hero">
            <div>
                <p>STUDENT ACHIEVEMENTS</p>
                <h1>My Certificates</h1>
                <p>View every certificate issued to you and download a PDF copy whenever you need one.</p>
            </div>
            <a href="index.php#courses"><i class="fa-solid fa-book-open"></i> Back to courses</a>
        </section>

        <?php if ($certificates): ?>
            <section class="cert-grid">
                <?php foreach ($certificates as $certificate): ?>
                    <article class="cert-card">
                        <h3><?= myA($certificate['course_name']) ?></h3>
                        <div class="cert-meta">
                            <span>Issued on: <?= myA(date('M d, Y', strtotime((string) $certificate['issued_on']))) ?></span>
                            <span>Certificate code: <?= myA($certificate['cert_code']) ?></span>
                            <span>Issuer: <?= myA($certificate['issuer_name'] ?: 'Jidanao LMS Admin') ?></span>
                        </div>
                        <div class="cert-actions">
                            <button
                                type="button"
                                class="primary"
                                onclick="generatePDF(<?= json_encode($studentName) ?>, <?= json_encode($certificate['course_name']) ?>, <?= json_encode($certificate['cert_code']) ?>, <?= json_encode(date('M d, Y', strtotime((string) $certificate['issued_on']))) ?>, <?= json_encode($certificate['issuer_name'] ?: 'Jidanao LMS Admin') ?>)"
                            >
                                <i class="fa-solid fa-download"></i> Download PDF Certificate
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php else: ?>
            <section class="empty">
                <i class="fa-solid fa-award" style="font-size: 34px; color: #2563eb;"></i>
                <h2 style="margin: 10px 0 6px;">No certificates yet</h2>
                <p>Your achievements will appear here once the admin issues one.</p>
            </section>
        <?php endif; ?>
    </main>

    <script>
        async function generatePDF(studentName, courseName, certCode, issueDate, issuerName) {
            const PdfLibrary = (window.jspdf && window.jspdf.jsPDF) ? window.jspdf.jsPDF : window.jsPDF;
            if (!PdfLibrary) {
                alert('PDF library is not available right now.');
                return;
            }

            const doc = new PdfLibrary({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            doc.setFillColor(245, 248, 253);
            doc.rect(0, 0, 297, 210, 'F');
            doc.setDrawColor(37, 99, 235);
            doc.setLineWidth(3);
            doc.rect(8, 8, 281, 194);
            doc.setLineWidth(1);
            doc.rect(13, 13, 271, 184);

            doc.setFont('helvetica', 'bold');
            doc.setTextColor(23, 33, 58);
            doc.setFontSize(34);
            doc.text('CERTIFICATE OF COMPLETION', 148.5, 44, { align: 'center' });

            doc.setFontSize(15);
            doc.setFont('helvetica', 'normal');
            doc.text('This is to certify that', 148.5, 67, { align: 'center' });

            doc.setFont('times', 'bolditalic');
            doc.setTextColor(37, 99, 235);
            doc.setFontSize(28);
            doc.text(studentName || 'Student Name', 148.5, 89, { align: 'center' });

            doc.setFont('helvetica', 'normal');
            doc.setTextColor(23, 33, 58);
            doc.setFontSize(15);
            doc.text('has successfully completed the requirements for', 148.5, 108, { align: 'center' });

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(20);
            doc.text(courseName || 'Course Certificate', 148.5, 125, { align: 'center' });

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(11);
            doc.text(`Issued on: ${issueDate}`, 24, 176);
            doc.text(`Certificate Code: ${certCode}`, 24, 183);
            doc.text(`Issuer: ${issuerName || 'Jidanao LMS Admin'}`, 24, 190);

            doc.setFont('helvetica', 'bold');
            doc.text('__________________________', 225, 176, { align: 'center' });
            doc.text('School Administrator', 225, 183, { align: 'center' });

            doc.save(`${(studentName || 'student').replace(/\s+/g, '_')}_${(courseName || 'certificate').replace(/\s+/g, '_')}.pdf`);
        }
    </script>
</body>
</html>
