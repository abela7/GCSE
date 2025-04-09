<?php
/**
 * Daily Vocabulary Notification Script
 * This script sends an email at 9:00 AM with vocabulary words to study for the day
 * Should be set to run via cron job at or shortly before 9:00 AM daily
 */

// Set timezone to London time
date_default_timezone_set('Europe/London');

// Enable error logging
error_log("==== VOCABULARY NOTIFICATION SCRIPT STARTED ====");
error_log("Script running at: " . date('Y-m-d H:i:s'));

// Include required files
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../templates/vocabulary_notification.php';

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

// Make sure this only runs around 9:00 AM (with a 15-minute buffer)
$current_hour = (int)date('H');
$current_minute = (int)date('i');

// Check if it's between 8:45 AM and 9:15 AM
$is_time_to_run = ($current_hour == 8 && $current_minute >= 45) || 
                  ($current_hour == 9 && $current_minute <= 15);

// For testing, you can comment out this condition
if (!$is_time_to_run && !isset($_GET['test'])) {
    error_log("Current time is " . date('H:i') . ". Not within the 9:00 AM notification window. Exiting.");
    exit;
}

// Get today's date
$today = date('Y-m-d');
error_log("Fetching vocabulary items for: " . $today);

// Check if we've already sent vocabulary notification today
$tracking_query = "SELECT * FROM task_notification_tracking 
                  WHERE notification_type = 'vocabulary' 
                  AND DATE(sent_at) = ?";
$tracking_stmt = $conn->prepare($tracking_query);
$tracking_stmt->bind_param("s", $today);
$tracking_stmt->execute();
$tracking_result = $tracking_stmt->get_result();

// Skip if already sent today (unless in test mode)
if ($tracking_result->num_rows > 0 && !isset($_GET['test'])) {
    error_log("Vocabulary notification already sent today. Exiting.");
    exit;
}

// Step 1: Get today's practice_day_id
$day_query = "SELECT id FROM practice_days WHERE practice_date = ?";
$day_stmt = $conn->prepare($day_query);
$day_stmt->bind_param("s", $today);
$day_stmt->execute();
$day_result = $day_stmt->get_result();

if ($day_result->num_rows === 0) {
    error_log("No practice day found for today's date. Using most recent day.");
    // Fallback to the most recent practice day
    $fallback_query = "SELECT id FROM practice_days ORDER BY practice_date DESC LIMIT 1";
    $fallback_result = $conn->query($fallback_query);
    
    if ($fallback_result->num_rows === 0) {
        error_log("No practice days found in database. Exiting.");
        exit;
    }
    
    $day_row = $fallback_result->fetch_assoc();
} else {
    $day_row = $day_result->fetch_assoc();
}

$practice_day_id = $day_row['id'];
error_log("Using practice day ID: " . $practice_day_id);

// Step 2: Get all practice categories
$categories_query = "SELECT id, name, description FROM practice_categories ORDER BY id";
$categories_result = $conn->query($categories_query);

if ($categories_result->num_rows === 0) {
    error_log("No practice categories found in database. Exiting.");
    exit;
}

$categories = [];
while ($category = $categories_result->fetch_assoc()) {
    $category['items'] = []; // Initialize empty items array
    $categories[$category['id']] = $category;
}

// Step 3: Get all items for the practice day, grouped by category
$items_query = "SELECT pi.id, pi.category_id, pi.item_title, pi.item_meaning, pi.item_example
                FROM practice_items pi
                WHERE pi.practice_day_id = ?
                ORDER BY pi.category_id, pi.id";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $practice_day_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$item_count = 0;
while ($item = $items_result->fetch_assoc()) {
    $category_id = $item['category_id'];
    if (isset($categories[$category_id])) {
        $categories[$category_id]['items'][] = $item;
        $item_count++;
    }
}

if ($item_count === 0) {
    error_log("No vocabulary items found for today. Exiting.");
    exit;
}

error_log("Found " . $item_count . " vocabulary items across " . count($categories) . " categories.");

// Prepare email data
$date_formatted = date('l, F j, Y');
$emailData = [
    'date_formatted' => $date_formatted,
    'categories' => $categories,
    'app_url' => $app_url
];

// Generate email content
$notification = new VocabularyNotification();
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
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = "Your Daily Vocabulary Words for " . date('F j');
    $mail->Body = $emailContent;
    $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
    
    // Send the email
    if ($mail->send()) {
        error_log("Vocabulary notification email sent successfully at " . date('Y-m-d H:i:s'));
        
        // Record that notification has been sent
        $tracking_query = "INSERT INTO task_notification_tracking (task_id, notification_type, sent_at)
                          VALUES (0, 'vocabulary', NOW())";
        $conn->query($tracking_query);
        
        error_log("Notification tracking record inserted.");
    } else {
        error_log("Email sending failed without exception");
    }
} catch (Exception $e) {
    error_log("PHPMailer Error: " . $e->getMessage());
}

// Close database connection
$conn->close();

error_log("==== VOCABULARY NOTIFICATION SCRIPT ENDED ====");
?> 