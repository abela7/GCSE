<?php
require_once 'vendor/autoload.php';
require_once 'config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = SMTP_AUTH;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;
    
    // Recipients
    $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
    $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
    $mail->addAddress(SMTP_USERNAME); // Sending to yourself for testing
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from GCSE Study App';
    $mail->Body = '
        <h1>Email Test Successful!</h1>
        <p>This is a test email from your GCSE Study App.</p>
        <p>If you are receiving this email, your email configuration is working correctly!</p>
        <p>Time sent: ' . date('Y-m-d H:i:s') . '</p>
    ';
    
    // Send the email
    $mail->send();
    echo '<div class="alert alert-success">Test email sent successfully! Please check your inbox.</div>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Email could not be sent. Error: ' . $mail->ErrorInfo . '</div>';
}
?> 