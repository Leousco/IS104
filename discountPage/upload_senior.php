<?php
session_start();
require_once "../config.php";
require '../vendor/autoload.php'; // path to Composer autoload.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if (!isset($_SESSION['UserID'])) {
    die("Error: You must be logged in to upload files.");
}

$user_id = $_SESSION['UserID'];

// ✅ Match your form field names
$required = ['idFront', 'idBack', 'proofDocument'];
$missing = [];

foreach ($required as $file) {
    if (!isset($_FILES[$file]) || $_FILES[$file]['error'] != 0) {
        $missing[] = $file;
    }
}

if (!empty($missing)) {
    die("Error: All required documents must be uploaded. Missing: " . implode(", ", $missing));
}

// ✅ Prepare upload directory
$uploadDir = "../uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// ✅ Generate unique file paths
$idFrontPath = $uploadDir . uniqid("front_") . "_" . basename($_FILES['idFront']['name']);
$idBackPath = $uploadDir . uniqid("back_") . "_" . basename($_FILES['idBack']['name']);
$proofDocPath = $uploadDir . uniqid("proof_") . "_" . basename($_FILES['proofDocument']['name']);

// ✅ Move uploaded files
move_uploaded_file($_FILES['idFront']['tmp_name'], $idFrontPath);
move_uploaded_file($_FILES['idBack']['tmp_name'], $idBackPath);
move_uploaded_file($_FILES['proofDocument']['tmp_name'], $proofDocPath);

// ✅ Collect user inputs safely
$fullName = trim($_POST['fullName'] ?? '');
$email = trim($_POST['email'] ?? '');
$age = trim($_POST['age'] ?? ''); 
$notes = trim($_POST['notes'] ?? '');

// ✅ Validate minimal input
if (empty($fullName) || empty($email) || empty($age)) {
    die("Error: Full name, email, and age are required.");
}

// ✅ Insert into main table as Senior category
$stmt = $conn->prepare("
    INSERT INTO discount_applications 
    (UserID, Category, FullName, Email, Age, ID_Front, ID_Back, ProofOfEnrollment, Notes, Status, SubmittedAt)
    VALUES (?, 'Senior', ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
");

if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param(
    "isssssss",
    $user_id,
    $fullName,
    $email,
    $age,
    $idFrontPath,
    $idBackPath,
    $proofDocPath,
    $notes
);


if ($stmt->execute()) {
    // Send confirmation email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'novacore.mailer@gmail.com';
        $mail->Password   = 'yjwc zsaa jltv vekq';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('no-reply@yourdomain.com', 'NovaCore');
        $mail->addAddress($email, $fullName);
        $mail->isHTML(true);
        $mail->Subject = "Senior Discount Application Submitted";

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9fafb;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
                <h1 style='color: white; margin: 0;'>🧓 Senior Discount Application Submitted!</h1>
            </div>
            <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                <h2 style='color: #333; margin-top: 0;'>Hi {$fullName},</h2>
                <p style='color: #666; font-size: 16px;'>We have received your senior discount application. Our team will review it and notify you once a decision is made.</p>

                <div style='background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #333; margin-top: 0;'>Application Details</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; color: #666;'><strong>Full Name:</strong></td>
                            <td style='padding: 8px 0; color: #333; text-align: right;'>{$fullName}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666;'><strong>Email:</strong></td>
                            <td style='padding: 8px 0; color: #333; text-align: right;'>{$email}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666;'><strong>Age:</strong></td>
                            <td style='padding: 8px 0; color: #333; text-align: right;'>{$age}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666;'><strong>Documents Uploaded:</strong></td>
                            <td style='padding: 8px 0; color: #333; text-align: right;'>ID Front, ID Back, Proof of Age</td>
                        </tr>
                    </table>
                </div>

                <p style='color: #666; font-size: 14px; margin-top: 20px;'>You can track the status of your application on your discount page.</p>

                <div style='text-align: center; margin-top: 30px;'>
                    <a href='http://localhost/SADPROJ/passenger_dashboard.php' style='background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold;'>Go to Dashboard</a>
                </div>
            </div>
            <div style='text-align: center; margin-top: 20px; color: #9ca3af; font-size: 12px;'>
                <p>Thank you for choosing NovaCore!</p>
            </div>
        </div>

        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }

    header("Location: Senior_Citizen_Verification.php?success=1");
    exit();
} else {
    die("Database Error: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>
