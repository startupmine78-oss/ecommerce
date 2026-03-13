<?php

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'bathvlegbattulga204@gmail.com'; 
    $mail->Password   = 'cujfaccehgcmjvcy'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('bathvlegbattulga204@gmail.com', 'ShopMN');
    $mail->addAddress('bathvlegbattulga204@gmail.com'); 

    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = '<h1>Амжилттай илгээлээ 🚀</h1>';

    $mail->send();
    echo "✅ Mail sent successfully!";

} catch (Exception $e) {
    echo "❌ Error: {$mail->ErrorInfo}";
}