<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'novacore.mailer@gmail.com';
    $mail->Password = 'yjwc zsaa jltv vekq';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('novacore.mailer@gmail.com', 'NovaCore Team');
    $mail->addAddress('your email here'); // <-- change this to your email

    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test. PHPMailer works!';

    $mail->send();
    echo "Test email sent!";
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
