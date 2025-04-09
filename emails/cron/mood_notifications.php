<?php
/**
 * Mood Tracking Notification Script
 * Sends emails to check user's mood three times daily: 12:00 PM, 6:00 PM, and 11:50 PM
 * Should be set up as a cron job to run every hour
 */

// Set timezone to London time
date_default_timezone_set('Europe/London');

// Enable error logging
error_log("==== MOOD TRACKING NOTIFICATION SCRIPT STARTED ====");
error_log("Script running at: " . date('Y-m-d H:i:s'));

// Include required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../templates/mood_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if database connection is successful
if ($conn->connect_error) {
    error_log("DATABASE CONNECTION ERROR: " . $conn->connect_error);
    exit;
} else {
    error_log("Database connection successful");
}

// Application URL for links in emails
$app_url = 'http://abel.abuneteklehaymanot.org';

// Only proceed if email notifications are enabled
if (!ENABLE_EMAIL_NOTIFICATIONS) {
    error_log("Email notifications are disabled in config. Exiting.");
    exit;
}

// Get current time and check if we should send a notification
$current_hour = (int)date('H');
$current_minute = (int)date('i');
$today = date('Y-m-d');

// Define notification times
$notification_times = [
    ['hour' => 12, 'minute' => 0, 'type' => 'mood_midday'],    // 12:00 PM
    ['hour' => 18, 'minute' => 0, 'type' => 'mood_evening'],   // 6:00 PM
    ['hour' => 23, 'minute' => 50, 'type' => 'mood_night']     // 11:50 PM
];

// Check if current time matches any of our notification times (with 5-minute buffer)
$notification_to_send = null;
foreach ($notification_times as $time) {
    // Check if current hour matches and we're within 5 minutes of the target time
    if ($current_hour == $time['hour'] && 
        abs($current_minute - $time['minute']) <= 5) {
        $notification_to_send = $time;
        break;
    }
}

// If no matching time or in test mode, exit
if (!$notification_to_send && !isset($_GET['test'])) {
    error_log("Current time " . date('H:i') . " does not match any mood notification times. Exiting.");
    exit;
}

// For testing, if test parameter is set with a specific type, use that instead
if (isset($_GET['test']) && isset($_GET['type'])) {
    $test_type = $_GET['type'];
    foreach ($notification_times as $time) {
        if ($time['type'] == $test_type) {
            $notification_to_send = $time;
            break;
        }
    }
    
    // If no valid type provided, use midday as default for testing
    if (!$notification_to_send) {
        $notification_to_send = $notification_times[0];
    }
    
    error_log("TEST MODE: Sending " . $notification_to_send['type'] . " notification");
} else if (isset($_GET['test'])) {
    // Just test parameter without type - use current time to determine which to send
    $hour = (int)date('H');
    if ($hour < 12) {
        $notification_to_send = $notification_times[0]; // Morning
    } else if ($hour < 18) {
        $notification_to_send = $notification_times[1]; // Afternoon
    } else {
        $notification_to_send = $notification_times[2]; // Evening
    }
    error_log("TEST MODE: Sending " . $notification_to_send['type'] . " notification based on current time");
}

// Check if we've already sent this notification type today
$tracking_query = "SELECT * FROM task_notification_tracking 
                  WHERE notification_type = ? 
                  AND DATE(sent_at) = ?";
$tracking_stmt = $conn->prepare($tracking_query);
$tracking_stmt->bind_param("ss", $notification_to_send['type'], $today);
$tracking_stmt->execute();
$tracking_result = $tracking_stmt->get_result();

// Skip if already sent today (unless in test mode)
if ($tracking_result->num_rows > 0 && !isset($_GET['test'])) {
    error_log("Mood notification for " . $notification_to_send['type'] . " already sent today. Exiting.");
    exit;
}

// Prepare different messages based on time of day
$messages = [
    'mood_midday' => [
        'greeting' => 'Midday Check-in',
        'message' => 'Time for my midday mood check-in. How am I feeling so far today? Taking a moment to reflect on my current state helps me be more mindful.',
        'period' => 'midday'
    ],
    'mood_evening' => [
        'greeting' => 'Evening Check-in',
        'message' => 'As the day winds down, I should check how I\'m feeling. Recording my mood now helps me identify patterns and improve my well-being.',
        'period' => 'evening'
    ],
    'mood_night' => [
        'greeting' => 'Night Check-in',
        'message' => 'Before I end my day, I need to take a moment to record how I am feeling. This helps track my emotional patterns over time.',
        'period' => 'night'
    ]
];

// Get appropriate message for current notification
$time_greeting = $messages[$notification_to_send['type']]['greeting'];
$message = $messages[$notification_to_send['type']]['message'];
$period = $messages[$notification_to_send['type']]['period'];

// Prepare email data
$exact_datetime = date('F j, Y, g:i A');
$emailData = [
    'time_greeting' => $time_greeting,
    'message' => $message,
    'period' => $period,
    'exact_datetime' => $exact_datetime,
    'app_url' => $app_url
];

// Generate email content
$notification = new MoodNotification();
$emailContent = $notification->generateEmail($emailData);

// Send email
$mail = new PHPMailer(true);

try {
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
    $mail->addAddress(SMTP_USERNAME);
    $mail->addReplyTo(EMAIL_REPLY_TO, EMAIL_FROM_NAME);
    
    // Anti-spam and deliverability improvements
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->XMailer = ' ';  // Hide PHPMailer version
    
    // Create unique Message-ID with timestamp to ensure new email each time
    $unique_id = time() . '.' . mt_rand() . '@abel.abuneteklehaymanot.org';
    $mail->MessageID = '<mood.' . $notification_to_send['type'] . '.' . $unique_id . '>';
    
    // Add DKIM signing headers
    $mail->DKIM_domain = 'abel.abuneteklehaymanot.org';
    $mail->DKIM_selector = 'email';  // Create this selector in your DNS
    $mail->DKIM_identity = $mail->From;
    
    // Add additional headers to improve deliverability
    $mail->addCustomHeader('List-Unsubscribe', '<mailto:' . EMAIL_REPLY_TO . '?subject=Unsubscribe>');
    $mail->addCustomHeader('Precedence', 'bulk');
    $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = "Reminder: How am I feeling? - " . $time_greeting . " (" . date('F j, g:i A') . ")";
    $mail->Body = $emailContent;
    $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
    
    // Send the email
    if ($mail->send()) {
        error_log("Mood tracking email (" . $notification_to_send['type'] . ") sent successfully at " . date('Y-m-d H:i:s'));
        
        // Record that notification has been sent
        $tracking_query = "INSERT INTO task_notification_tracking (task_id, notification_type, sent_at)
                          VALUES (0, ?, NOW())";
        $tracking_stmt = $conn->prepare($tracking_query);
        $tracking_stmt->bind_param("s", $notification_to_send['type']);
        $tracking_stmt->execute();
        
        error_log("Notification tracking record inserted for " . $notification_to_send['type']);
    } else {
        error_log("Email sending failed without exception");
    }
} catch (Exception $e) {
    error_log("PHPMailer Error: " . $e->getMessage());
}

// Close database connection
$conn->close();

error_log("==== MOOD TRACKING NOTIFICATION SCRIPT ENDED ====");
?> 