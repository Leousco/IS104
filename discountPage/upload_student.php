<?php
session_start();
require_once "../config.php"; 
require '../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure the user is logged in
if (!isset($_SESSION['UserID'])) {
    die("Error: You must be logged in to submit a verification.");
}

$user_id = $_SESSION['UserID'];

// File upload paths
$uploadDir = "../uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Helper function to upload files
function uploadFile($fileInputName, $uploadDir) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES[$fileInputName]['tmp_name'];
        $originalName = basename($_FILES[$fileInputName]['name']);
        $uniqueName = time() . "_" . preg_replace("/[^A-Za-z0-9_.-]/", "_", $originalName);
        $targetPath = $uploadDir . $uniqueName;
        if (move_uploaded_file($tmpName, $targetPath)) {
            return "uploads/" . $uniqueName; 
        }
    }
    return null;
}

// Upload files
$id_front  = uploadFile("idFront",  $uploadDir);
$id_back   = uploadFile("idBack",   $uploadDir);
$proof_doc = uploadFile("proofOfEnrollment", $uploadDir);

// Gather text fields
$full_name = $_POST['fullName'] ?? '';
$email     = $_POST['email'] ?? '';
$school    = $_POST['school'] ?? '';
$notes     = $_POST['notes'] ?? '';

// Validate required fields
if (!$id_front || !$id_back || !$proof_doc) {
    die("Error: All required documents must be uploaded.");
}

// Insert into database
$stmt = $conn->prepare("INSERT INTO discount_applications 
    (UserID, ID_Front, ID_Back, ProofOfEnrollment, FullName, Email, School, Notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssssss", $user_id, $id_front, $id_back, $proof_doc, $full_name, $email, $school, $notes);

if ($stmt->execute()) {

    // --- SEND EMAIL USING PHPMailer ---
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // replace with your SMTP host (gmail.com)
        $mail->SMTPAuth   = true;
        $mail->Username   = 'novacore.mailer@gmail.com'; // your SMTP username
        $mail->Password   = 'yjwc zsaa jltv vekq'; // SMTP password or API key
        $mail->SMTPSecure = 'tls'; // or 'ssl'
        $mail->Port       = 587;   // 465 for ssl, 587 for tls

        // Recipients
        $mail->setFrom('no-reply@yourdomain.com', 'NovaCore');
        $mail->addAddress($email, $full_name);
        $mail->addReplyTo('support@yourdomain.com', 'Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Student Discount Application Submitted';

        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9fafb;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
                    <h1 style='color: white; margin: 0;'>🎓 Application Submitted!</h1>
                </div>
                <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                    <h2 style='color: #333; margin-top: 0;'>Hi {$full_name},</h2>
                    <p style='color: #666; font-size: 16px;'>We have received your student discount application. Our team will review it and notify you once a decision is made.</p>

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
                                <td style='padding: 8px 0; color: #666;'><strong>School:</strong></td>
                                <td style='padding: 8px 0; color: #333; text-align: right;'>{$school}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Documents Uploaded:</strong></td>
                                <td style='padding: 8px 0; color: #333; text-align: right;'>ID Front, ID Back, Proof of Enrollment</td>
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
        // Success alert & redirect
        echo "<script>alert('Your verification has been submitted successfully! A confirmation email has been sent.'); 
              window.location.href='student.php';</script>";

    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

} else {
    echo "Database Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
