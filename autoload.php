<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@gmail.com';
    $mail->Password = 'your-app-password';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('your-email@gmail.com', 'Test');
    $mail->addAddress('your-email@gmail.com');
    $mail->Subject = 'SMTP Test';
    $mail->Body = 'It works!';
    $mail->send();
    echo "Mail sent!";
} catch (Exception $e) {
    echo "Mailer Error: " . $mail->ErrorInfo;
}