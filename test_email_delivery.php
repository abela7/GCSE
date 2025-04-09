<?php
/**
 * Email Delivery Test Script
 * This script tests email delivery by sending a simple test email
 */

// Include required files
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Add basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Email Delivery Test - Amha-Silassie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        pre { max-height: 500px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Email Delivery Test</h1>';

// If form submitted, send a test email
if (isset($_POST['send_test'])) {
    $recipient = isset($_POST['recipient']) ? $_POST['recipient'] : 'Abelgoytom77@gmail.com';
    $subject = isset($_POST['subject']) ? $_POST['subject'] : 'Test Email from Amha-Silassie';
    
    // Collect SMTP details for debugging
    echo '<div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5>SMTP Configuration</h5>
        </div>
        <div class="card-body">
            <ul>
                <li><strong>Host:</strong> ' . SMTP_HOST . '</li>
                <li><strong>Port:</strong> ' . SMTP_PORT . '</li>
                <li><strong>Authentication:</strong> ' . (SMTP_AUTH ? 'Yes' : 'No') . '</li>
                <li><strong>Secure:</strong> ' . SMTP_SECURE . '</li>
                <li><strong>Username:</strong> ' . SMTP_USERNAME . '</li>
                <li><strong>Sending Email:</strong> ' . EMAIL_FROM_ADDRESS . '</li>
                <li><strong>Notifications Enabled:</strong> ' . (ENABLE_EMAIL_NOTIFICATIONS ? 'Yes' : 'No') . '</li>
            </ul>
        </div>
    </div>';
    
    // Create PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings with debugging
        $mail->SMTPDebug = 3; // Enable debug output
        $mail->Debugoutput = function($str, $level) {
            echo "<div class='debug-output'>" . htmlspecialchars($str) . "</div>";
        };
        
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Anti-spam measures
        $mail->XMailer = 'GCSE Study App Mailer';
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
        $mail->addCustomHeader('Precedence', 'bulk');
        $mail->addCustomHeader('X-Priority', '3'); // Normal priority
        
        // Recipients
        $mail->setFrom(SMTP_USERNAME, EMAIL_FROM_NAME);
        $mail->addAddress($recipient);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Create a simple HTML email
        $htmlContent = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd;">
            <h2 style="color: #333;">Test Email from Amha-Silassie</h2>
            <p>This is a test email sent at: <strong>' . date('Y-m-d H:i:s') . '</strong></p>
            <p>If you\'re seeing this, email delivery is working correctly!</p>
            <hr>
            <p style="font-size: 12px; color: #777;">This is an automated test message.</p>
        </div>
        ';
        
        $textContent = "Test Email from Amha-Silassie\n\n";
        $textContent .= "This is a test email sent at: " . date('Y-m-d H:i:s') . "\n\n";
        $textContent .= "If you're seeing this, email delivery is working correctly!\n\n";
        $textContent .= "This is an automated test message.";
        
        $mail->Body = $htmlContent;
        $mail->AltBody = $textContent;
        
        // Attempt to send
        echo '<div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5>SMTP Debug Log</h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3">';
        
        $result = $mail->send();
        
        echo '</pre>
            </div>
        </div>';
        
        echo '<div class="alert alert-success">
            <strong>Success!</strong> Test email sent to ' . htmlspecialchars($recipient) . '
        </div>';
        
    } catch (Exception $e) {
        echo '</pre>
            </div>
        </div>';
        
        echo '<div class="alert alert-danger">
            <strong>Error:</strong> ' . $mail->ErrorInfo . '
        </div>';
    }
}

// Display the email test form
echo '<div class="card">
    <div class="card-header bg-primary text-white">
        <h5>Send Test Email</h5>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label for="recipient" class="form-label">Recipient Email</label>
                <input type="email" class="form-control" id="recipient" name="recipient" 
                    value="Abelgoytom77@gmail.com" required>
                <div class="form-text">Email address to send the test message to</div>
            </div>
            
            <div class="mb-3">
                <label for="subject" class="form-label">Email Subject</label>
                <input type="text" class="form-control" id="subject" name="subject" 
                    value="Test Email from Amha-Silassie">
            </div>
            
            <button type="submit" name="send_test" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Send Test Email
            </button>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header bg-info text-white">
        <h5>Troubleshooting Tips</h5>
    </div>
    <div class="card-body">
        <ol>
            <li>Check that SMTP settings are correct (especially host, port, and credentials)</li>
            <li>Verify that your email server allows relay from this IP address</li>
            <li>If using Gmail, make sure "Less secure app access" is enabled or use App Passwords</li>
            <li>Check firewall settings to ensure the needed ports are open</li>
            <li>Verify DNS records (MX, SPF, DKIM) are properly configured</li>
            <li>Try a different port (587 instead of 465) or security method (TLS instead of SSL)</li>
        </ol>
    </div>
</div>

<p class="mt-3">
    <a href="pages/scheduled-notifications-test.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Notification Dashboard
    </a>
</p>

</div>
</body>
</html>';
?> 