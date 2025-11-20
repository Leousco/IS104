<?php
session_start();
require_once "../config.php";
require '../vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ Ensure user is logged in
if (!isset($_SESSION['UserID'])) {
    die("Error: You must be logged in to submit this form.");
}

$user_id = (int) $_SESSION['UserID'];

// ✅ Collect form inputs
$full_name = trim($_POST['fullName'] ?? '');
$email = trim($_POST['email'] ?? '');
$agency = trim($_POST['agency'] ?? ''); // stored in School column
$notes = trim($_POST['notes'] ?? '');

// ✅ Validate required fields
if ($full_name === '' || $email === '' || $agency === '') {
    die("Error: Please fill out all required fields.");
}

// ✅ Validate required file uploads
$required_files = ['idFront', 'idBack', 'proofOfEmployment'];
foreach ($required_files as $file) {
    if (!isset($_FILES[$file]) || $_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
        die("Error: Please upload all required documents (Front ID, Back ID, Proof of Employment).");
    }
}

// ✅ Ensure upload directory exists
$upload_dir = "../uploads/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ✅ File upload helper function
function uploadFile($fileArr, $prefix, $upload_dir) {
    $ext = pathinfo($fileArr['name'], PATHINFO_EXTENSION);
    $file_name = $prefix . "_" . time() . "_" . uniqid() . "." . $ext;
    $file_path = $upload_dir . $file_name;
    if (!move_uploaded_file($fileArr['tmp_name'], $file_path)) {
        die("Error: Failed to upload " . htmlspecialchars($fileArr['name']));
    }
    return "uploads/" . $file_name; // store relative path in DB
}

// ✅ Upload files
$id_front = uploadFile($_FILES['idFront'], 'gov_front', $upload_dir);
$id_back = uploadFile($_FILES['idBack'], 'gov_back', $upload_dir);
$proof = uploadFile($_FILES['proofOfEmployment'], 'gov_proof', $upload_dir);

// ✅ Insert into discount_applications
$stmt = $conn->prepare("
    INSERT INTO discount_applications 
    (UserID, FullName, Email, Agency, Notes, ID_Front, ID_Back, ProofOfEnrollment, Category, Status, SubmittedAt)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Government', 'Pending', NOW())
");

if (!$stmt) {
    die("Database prepare failed: " . $conn->error);
}

$stmt->bind_param(
    "isssssss",
    $user_id,
    $full_name,
    $email,
    $agency,
    $notes,
    $id_front,
    $id_back,
    $proof
);

if ($stmt->execute()) {
    // ✅ Send confirmation email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'novacore.mailer@gmail.com';
        $mail->Password   = 'yjwc zsaa jltv vekq'; // use app password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('no-reply@yourdomain.com', 'NovaCore');
        $mail->addAddress($email, $full_name);
        $mail->isHTML(true);
        $mail->Subject = "Government Employee Verification Submitted";

        // ✅ Styled email body
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9fafb;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
                <h1 style='color: white; margin: 0;'>🏢 Government Verification Submitted</h1>
            </div>
            <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                <h2 style='color: #333; margin-top: 0;'>Hi {$full_name},</h2>
                <p style='color: #666; font-size: 16px;'>Your government employee verification application has been submitted successfully. Our team will review your documents and notify you once a decision is made.</p>

                <div style='background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #333; margin-top: 0;'>Application Details</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; color: #666;'><strong>Full Name:</strong></td>
                            <td style='padding: 8px 0; color: #333; text-align: right;'>{$full_name}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666;'><strong>Email:</strong></td>
                            <td style='padding: 8px 0; color: #333; text-align: right;'>{$email}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666;'><strong>Agency:</strong></td>
                            <td style='padding: 8px 0; color: #333; text-align: right;'>{$agency}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666;'><strong>Documents Uploaded:</strong></td>
                            <td style='padding: 8px 0; color: #333; text-align: right;'>ID Front, ID Back, Proof of Employment</td>
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

    // ✅ Redirect after success
    header("Location: Government_Verification.php?success=1");
    exit();
} else {
    die("Database Error: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>
