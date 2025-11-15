<?php
session_start();
include("../config.php");

// MUST be logged in
if (!isset($_SESSION['UserID'])) {
    die("Unauthorized.");
}

// MUST have deal_id from redirect
if (!isset($_GET['deal_id']) || !isset($_GET['external_id'])) {
    die("Invalid payment reference.");
}

$deal_id = intval($_GET['deal_id']);
$external_id = $_GET['external_id'];
$user_id = $_SESSION['UserID'];
$user_email = $_SESSION['Email'];

// Fetch deal details
$stmt = $conn->prepare("SELECT * FROM coin_deals WHERE DealID=? LIMIT 1");
$stmt->bind_param("i", $deal_id);
$stmt->execute();
$deal = $stmt->get_result()->fetch_assoc();

if (!$deal) {
    die("Deal not found.");
}

// Find the transaction
$stmt = $conn->prepare("
    SELECT * FROM coin_transactions 
    WHERE UserID=? AND DealID=? AND Status='PENDING'
    AND PaymentMethod LIKE ?
    ORDER BY TransactionID DESC LIMIT 1
");
$payment_ref = "%$external_id%";
$stmt->bind_param("iis", $user_id, $deal_id, $payment_ref);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

if (!$transaction) {
    die("Transaction not found.");
}

// Update transaction status to COMPLETED
$stmt = $conn->prepare("UPDATE coin_transactions SET Status='COMPLETED' WHERE TransactionID=?");
$stmt->bind_param("i", $transaction['TransactionID']);
$stmt->execute();

// Add coins to user's balance
$stmt = $conn->prepare("
    UPDATE users 
    SET balance = balance + ? 
    WHERE UserID=?
");
$stmt->bind_param("ii", $deal['CoinAmount'], $user_id);
$stmt->execute();

// Get updated balance
$stmt = $conn->prepare("SELECT balance FROM users WHERE UserID=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$new_balance = $user_data['balance'];

// ============================
// SEND EMAIL WITH PHPMailer
// ============================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require Composer autoloader
require '../vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // SMTP CONFIG
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    // CHANGE THESE ↓↓↓
    $mail->Username = 'novacore.mailer@gmail.com';
    $mail->Password = 'yjwc zsaa jltv vekq';  // Gmail App Password
    // CHANGE THESE ↑↑↑

    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('novacore.mailer@gmail.com', 'NovaCore Team');
    $mail->addAddress($user_email);

    // Email Body
    $mail->isHTML(true);
    $mail->Subject = "Coin Purchase Confirmation";

    $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9fafb;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
                <h1 style='color: white; margin: 0;'>🎉 Payment Successful!</h1>
            </div>
            <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                <h2 style='color: #333; margin-top: 0;'>Thank you for your purchase!</h2>
                <p style='color: #666; font-size: 16px;'>Your coins have been successfully added to your account.</p>
                
                <div style='background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #333; margin-top: 0;'>Purchase Details</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; color: #666;'><strong>Deal:</strong></td>
                            <td style='padding: 8px 0; color: #333; text-align: right;'>{$deal['DealName']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666;'><strong>Coins Purchased:</strong></td>
                            <td style='padding: 8px 0; color: #f59e0b; text-align: right; font-size: 20px; font-weight: bold;'>" . number_format($deal['CoinAmount']) . " coins</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666;'><strong>Amount Paid:</strong></td>
                            <td style='padding: 8px 0; color: #22c55e; text-align: right; font-size: 18px; font-weight: bold;'>₱" . number_format($deal['Price'], 2) . "</td>
                        </tr>
                        <tr style='border-top: 2px solid #e5e7eb;'>
                            <td style='padding: 12px 0; color: #666;'><strong>New Balance:</strong></td>
                            <td style='padding: 12px 0; color: #667eea; text-align: right; font-size: 24px; font-weight: bold;'>" . number_format($new_balance) . " coins</td>
                        </tr>
                    </table>
                </div>
                
                <p style='color: #666; font-size: 14px; margin-top: 20px;'>You can now use your coins to purchase tickets and access our services!</p>
                
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='http://localhost/SADPROJ/passenger_dashboard.php' style='background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold;'>Go to Dashboard</a>
                </div>
            </div>
            <div style='text-align: center; margin-top: 20px; color: #9ca3af; font-size: 12px;'>
                <p>Transaction ID: #{$transaction['TransactionID']}</p>
                <p>Thank you for choosing NovaCore!</p>
            </div>
        </div>
    ";

    $mail->send();
    $email_sent = true;

} catch (Exception $e) {
    $email_sent = false;
    $email_error = $mail->ErrorInfo;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .success-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: scaleIn 0.5s ease 0.3s both;
        }
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        .success-icon i {
            font-size: 40px;
            color: white;
        }
        h1 {
            color: #22c55e;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .details {
            background: #f9fafb;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 2px solid #e5e7eb;
        }
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            font-weight: bold;
            color: #333;
        }
        .detail-value.coins {
            color: #f59e0b;
            font-size: 20px;
        }
        .detail-value.balance {
            color: #16a34a;
            font-size: 24px;
        }
        .email-status {
            background: #d1fae5;
            color: #065f46;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .email-status.error {
            background: #fee2e2;
            color: #991b1b;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            padding: 14px 40px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
            margin-top: 10px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(34, 197, 94, 0.4);
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h1>Payment Successful!</h1>
        <p class="subtitle">Your coins have been added to your account</p>

        <?php if ($email_sent): ?>
            <div class="email-status">
                <i class="fas fa-envelope"></i> Confirmation email sent successfully!
            </div>
        <?php else: ?>
            <div class="email-status error">
                <i class="fas fa-exclamation-triangle"></i> Email delivery failed, but your coins were added.
            </div>
        <?php endif; ?>

        <div class="details">
            <div class="detail-row">
                <span class="detail-label">Deal Purchased:</span>
                <span class="detail-value"><?= htmlspecialchars($deal['DealName']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Coins Received:</span>
                <span class="detail-value coins">
                    <i class="fas fa-coins"></i> <?= number_format($deal['CoinAmount']) ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount Paid:</span>
                <span class="detail-value">₱<?= number_format($deal['Price'], 2) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">New Balance:</span>
                <span class="detail-value balance">
                    <i class="fas fa-wallet"></i> <?= number_format($new_balance) ?> coins
                </span>
            </div>
        </div>

        <a href="../passenger_dashboard.php" class="btn">
            <i class="fas fa-home"></i> Go to Dashboard
        </a>
    </div>
</body>
</html>