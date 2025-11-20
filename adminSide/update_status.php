<?php
session_start();
require_once "../config.php";
require '../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== "ADMIN") {
    die("unauthorized");
}

if (isset($_POST['application_id'], $_POST['status'])) {

    $app_id = intval($_POST['application_id']);
    $status = $_POST['status'];

    if (!in_array($status, ['Approved', 'Rejected'])) {
        die("invalid_status");
    }

    // UPDATE status
    $stmt = $conn->prepare("
        UPDATE discount_applications 
        SET Status = ?, ReviewedAt = NOW() 
        WHERE ApplicationID = ?
    ");
    $stmt->bind_param("si", $status, $app_id);

    if ($stmt->execute()) {

        // GET USER + CATEGORY info
        $stmt2 = $conn->prepare("
            SELECT FullName, Email, Age, School, Disability, Agency, Category 
            FROM discount_applications 
            WHERE ApplicationID = ?
        ");
        $stmt2->bind_param("i", $app_id);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $user = $result->fetch_assoc();
        $stmt2->close();

        if ($user) {
            $full_name  = $user['FullName'];
            $email      = $user['Email'];
            $school     = $user['School'];
            $age        = $user['Age'];
            $disability = $user['Disability'];
            $agency     = $user['Agency']; 
            $category   = $user['Category'];  

            // CATEGORY-SPECIFIC LABELS
            $category_labels = [
                'Student'    => ['label' => 'School', 'emoji' => '🎓', 'desc' => 'student discount'],
                'Senior'     => ['label' => 'Age', 'emoji' => '👴', 'desc' => 'senior citizen discount'],
                'PWD'        => ['label' => 'Disability Type', 'emoji' => '♿', 'desc' => 'PWD discount'],
                'Government' => ['label' => 'Agency', 'emoji' => '🏢', 'desc' => 'government employee discount']
            ];

            // fall back in case of weird data
            $cat = $category_labels[$category] ?? $category_labels['Student'];

            $field_label = $cat['label'];
            $cat_emoji   = $cat['emoji'];
            $cat_desc    = $cat['desc'];

            $field_value = match($category) {
                'Senior' => $age,
                'Student' => $school,
                'PWD' => $disability,
                'Government' => $agency,
                default => ''
            };            

            // EMAIL message text based on status
            if ($status === "Approved") {
                $statusMessage = "Good news! Your {$cat_desc} application has been approved. You now qualify for discounted fares.";
                $emoji = "✅";
                $color = "#22c55e";
            } else {
                $statusMessage = "Unfortunately, your {$cat_desc} application has been rejected. You may reapply after the waiting period. (7 Days)";
                $emoji = "❌";
                $color = "#ef4444";
            }

            // SEND EMAIL
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
                $mail->addAddress($email, $full_name);
                $mail->isHTML(true);

                // SUBJECT depends on category
                $mail->Subject = "{$cat_emoji} Your {$category} Discount Application has been {$status}";

                // MASTER HTML TEMPLATE
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; background-color: #f9fafb;'>
                    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
                        <h1 style='color: white; margin: 0;'>{$emoji} Application {$status}</h1>
                    </div>

                    <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);'>
                        <h2 style='color: #333;'>Hi {$full_name},</h2>
                        <p style='color: #666; font-size: 16px;'>{$statusMessage}</p>

                        <div style='background: #f3f4f6; padding: 20px; border-radius: 8px; margin-top: 20px;'>
                            <h3 style='color: #333; margin-top: 0;'>Application Details</h3>

                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 8px 0; color: #666;'><strong>Category:</strong></td>
                                    <td style='padding: 8px 0; color: #333; text-align: right;'>{$category}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; color: #666;'><strong>Full Name:</strong></td>
                                    <td style='padding: 8px 0; color: #333; text-align: right;'>{$full_name}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; color: #666;'><strong>{$field_label}:</strong></td>
                                    <td style='padding: 8px 0; color: #333; text-align: right;'>{$field_value}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; color: #666;'><strong>Status:</strong></td>
                                    <td style='padding: 8px 0; color: {$color}; text-align: right; font-weight: bold;'>{$status}</td>
                                </tr>
                            </table>
                        </div>

                        <div style='text-align: center; margin-top: 30px;'>
                            <a href='http://localhost/SADPROJ/passenger_dashboard.php' 
                            style='background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); 
                            color: white; padding: 12px 30px; 
                            border-radius: 8px; text-decoration: none; 
                            font-weight: bold;'>View Status</a>
                        </div>
                    </div>

                    <div style='text-align: center; margin-top: 20px; color: #9ca3af; font-size: 12px;'>
                        <p>Thank you for choosing NovaCore!</p>
                    </div>
                </div>
                ";

                $mail->send();

            } catch (Exception $e) {
                error_log("EMAIL ERROR: " . $mail->ErrorInfo);
            }
        }

        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'error' => 'db_error']);
    }

    $stmt->close();
    $conn->close();

} else {
    echo json_encode(['success' => false, 'error' => 'missing_data']);
}
?>
