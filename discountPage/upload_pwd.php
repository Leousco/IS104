<?php
session_start();
require_once "../config.php";
require '../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['UserID'])) {
    die("Error: You must be logged in to upload documents.");
}

$user_id = (int) $_SESSION['UserID'];

// ✅ Check required files
$requiredFiles = ['idFront', 'idBack', 'medicalCert'];
foreach ($requiredFiles as $file) {
    if (!isset($_FILES[$file]) || $_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
        die("Error: All required documents must be uploaded. Missing: $file");
    }
}

// ✅ Upload directory
$uploadDir = "../uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// ✅ Save uploaded files
function saveFile($file, $prefix) {
    global $uploadDir;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = $prefix . "_" . time() . "_" . uniqid() . "." . $ext;
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        die("Error: Failed to upload " . htmlspecialchars($file['name']));
    }

    return "uploads/" . $fileName; // relative path for DB
}

$idFrontPath     = saveFile($_FILES['idFront'], "pwd_front");
$idBackPath      = saveFile($_FILES['idBack'], "pwd_back");
$medicalCertPath = saveFile($_FILES['medicalCert'], "pwd_medical");

// ✅ Form inputs
$fullName       = trim($_POST['fullName'] ?? '');
$email          = trim($_POST['email'] ?? '');
$disabilityType = trim($_POST['disabilityType'] ?? '');
$notes          = trim($_POST['notes'] ?? '');

if (empty($fullName) || empty($email) || empty($disabilityType)) {
    die("Error: Please fill in all required fields.");
}

// ✅ Insert into database
$query = "INSERT INTO discount_applications 
    (UserID, Category, FullName, Email, ID_Front, ID_Back, ProofOfEnrollment, Disability, Notes, Status, SubmittedAt) 
    VALUES (?, 'PWD', ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("SQL prepare() failed: " . $conn->error);
}

$stmt->bind_param(
    "isssssss",
    $user_id,
    $fullName,
    $email,
    $idFrontPath,
    $idBackPath,
    $medicalCertPath,
    $disabilityType,
    $notes
);

if (!$stmt->execute()) {
    die("Error: Could not save application. " . $stmt->error);
}

$stmt->close();
$conn->close();

// ✅ Send confirmation email
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
    $mail->Subject = "PWD Discount Application Submitted";

    $mail->Body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9fafb;'>
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
            <h1 style='color: white; margin: 0;'>♿ PWD Discount Application Submitted</h1>
        </div>
        <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
            <h2 style='color: #333; margin-top: 0;'>Hi {$fullName},</h2>
            <p style='color: #666; font-size: 16px;'>We have received your PWD discount application. Our team will review it and notify you once a decision is made.</p>

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
                        <td style='padding: 8px 0; color: #666;'><strong>Disability Type:</strong></td>
                        <td style='padding: 8px 0; color: #333; text-align: right;'>{$disabilityType}</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; color: #666;'><strong>Documents Uploaded:</strong></td>
                        <td style='padding: 8px 0; color: #333; text-align: right;'>ID Front, ID Back, Medical Certificate</td>
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

// ✅ Redirect after successful upload
header("Location: PWD_Verification.php?success=1");
exit();
?>
