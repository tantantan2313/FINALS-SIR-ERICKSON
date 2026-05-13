<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

/**
 * Send email using SMTP (works on InfinityFree)
 */
function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // 🔥 SMTP SETTINGS
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // or your SMTP provider
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tristanduque66@gmail.com';
        $mail->Password   = 'zpfh ysab ehoy vggs'; // NOT your normal password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // 🔥 SENDER INFO
        $mail->setFrom('tristanduque66@gmail.com', 'School Transport System');
        $mail->addAddress($to);

        // 🔥 CONTENT
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        return $mail->send();

    } catch (Exception $e) {
        // Optional: log error for debugging
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}