<?php
/**
 * Test Habit Notification Script
 * This script allows manually testing the habit notification email
 */

// Include required files
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/email_config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/emails/templates/habit_notification.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Application URL for links in emails
$app_url = 'http://abel.abuneteklehaymanot.org';

// Add basic styling
echo '<!DOCTYPE html>
<html>
<head>
    <title>Habit Notification Test - Amha-Silassie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Habit Notification Test - Amha-Silassie</h1>';

// Get all habits or select a specific habit
$habit_id = isset($_GET['habit_id']) ? (int)$_GET['habit_id'] : null;

// If form submitted, send a test notification
if (isset($_POST['send_test']) && isset($_POST['habit_id'])) {
    $habit_id = (int)$_POST['habit_id'];
    
    // Get the habit details
    $habit_query = "
        SELECT 
            h.id, 
            h.name AS title, 
            h.description, 
            hpr.completion_points AS points,
            h.target_time AS due_time,
            h.category_id,
            CASE 
                WHEN hpr.completion_points >= 20 THEN 'high'
                WHEN hpr.completion_points >= 10 THEN 'medium'
                ELSE 'low'
            END AS priority,
            CASE 
                WHEN hc.name IS NOT NULL THEN hc.name 
                ELSE 'Uncategorized' 
            END AS category_name
        FROM 
            habits h
        LEFT JOIN 
            habit_categories hc ON h.category_id = hc.id
        LEFT JOIN
            habit_point_rules hpr ON h.point_rule_id = hpr.id
        WHERE 
            h.id = ?
    ";
    
    $stmt = $conn->prepare($habit_query);
    $stmt->bind_param("i", $habit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo '<div class="alert alert-danger">Habit not found!</div>';
    } else {
        $current_habit = $result->fetch_assoc();
        
        // Format habit time for display
        $current_habit['due_time'] = date('h:i A', strtotime($current_habit['due_time']));
        
        // Get other pending habits for today
        $pending_habits_query = "
            SELECT 
                h.id, 
                h.name AS title, 
                h.description, 
                h.target_time AS due_time,
                h.category_id,
                CASE 
                    WHEN hpr.completion_points >= 20 THEN 'high'
                    WHEN hpr.completion_points >= 10 THEN 'medium'
                    ELSE 'low'
                END AS priority,
                CASE 
                    WHEN hc.name IS NOT NULL THEN hc.name 
                    ELSE 'Uncategorized' 
                END AS category_name
            FROM 
                habits h
            LEFT JOIN 
                habit_categories hc ON h.category_id = hc.id
            LEFT JOIN
                habit_point_rules hpr ON h.point_rule_id = hpr.id
            LEFT JOIN 
                habit_completions hcp ON h.id = hcp.habit_id AND hcp.completion_date = CURRENT_DATE
            WHERE 
                h.is_active = 1
                AND hcp.id IS NULL
                AND h.id != ?
            ORDER BY 
                h.target_time ASC
            LIMIT 3
        ";
        
        $pending_stmt = $conn->prepare($pending_habits_query);
        $pending_stmt->bind_param("i", $habit_id);
        $pending_stmt->execute();
        $pending_result = $pending_stmt->get_result();
        
        $pending_habits = [];
        while ($habit = $pending_result->fetch_assoc()) {
            $habit['due_time'] = date('h:i A', strtotime($habit['due_time']));
            $habit['estimated_duration'] = 'Pending';
            $pending_habits[] = $habit;
        }
        
        // Add estimate duration for current habit
        $current_habit['estimated_duration'] = '';
        
        // Prepare email data
        $emailData = [
            'current_task' => $current_habit,
            'overdue_tasks' => [],
            'upcoming_tasks' => $pending_habits,
            'app_url' => $app_url
        ];
        
        // Generate email content
        $notification = new HabitNotification();
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
            
            // Add custom headers to reduce spam score
            $mail->XMailer = 'GCSE Study App Mailer';
            $mail->addCustomHeader('X-Application', 'GCSE Study App');
            $mail->addCustomHeader('X-Domain-ID', 'abel.abuneteklehaymanot.org');
            
            // Recipients
            $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
            $mail->addAddress('Abelgoytom77@gmail.com'); // Send to Abel's Gmail
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "TEST - Habit Due Now: " . $current_habit['title'];
            $mail->Body = $emailContent;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</div>'], "\n", $emailContent));
            
            // Send the email
            $mail->send();
            echo '<div class="alert alert-success">
                    <h4 class="alert-heading">Success!</h4>
                    <p>Test habit notification email sent successfully! Please check your inbox at Abelgoytom77@gmail.com</p>
                    <hr>
                    <p class="mb-0">If you don\'t see the email, please check your spam folder.</p>
                  </div>';
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">
                    <h4 class="alert-heading">Error!</h4>
                    <p>Email could not be sent. Error details:</p>
                    <pre>' . $mail->ErrorInfo . '</pre>
                  </div>';
        }
    }
}

// Display form to select a habit
echo '<div class="card">
        <div class="card-header">
            <h2>Send Test Habit Notification</h2>
        </div>
        <div class="card-body">
            <form method="post" action="test_habit_notification.php">
                <div class="mb-3">
                    <label for="habit_id" class="form-label">Select a Habit:</label>
                    <select name="habit_id" id="habit_id" class="form-select" required>';

// Get habits to populate dropdown
$habits_query = "
    SELECT 
        h.id, 
        h.name, 
        h.target_time,
        hpr.completion_points,
        CASE WHEN hc.name IS NOT NULL THEN hc.name ELSE 'Uncategorized' END AS category_name
    FROM 
        habits h
    LEFT JOIN 
        habit_categories hc ON h.category_id = hc.id
    LEFT JOIN
        habit_point_rules hpr ON h.point_rule_id = hpr.id
    WHERE 
        h.is_active = 1
    ORDER BY 
        h.target_time ASC
    LIMIT 20
";

$habits_result = $conn->query($habits_query);
if ($habits_result->num_rows > 0) {
    while ($habit = $habits_result->fetch_assoc()) {
        $selected = ($habit_id == $habit['id']) ? 'selected' : '';
        $target_time = $habit['target_time'] ? date('h:i A', strtotime($habit['target_time'])) : 'No time';
        echo '<option value="' . $habit['id'] . '" ' . $selected . '>' . 
             htmlspecialchars($habit['name']) . ' - ' . 
             $target_time . 
             ' (' . ($habit['completion_points'] ? '+' . $habit['completion_points'] . ' points' : 'No points') . ', ' . $habit['category_name'] . ')' .
             '</option>';
    }
} else {
    echo '<option value="">No habits found</option>';
}

echo '          </select>
                </div>
                <button type="submit" name="send_test" class="btn btn-primary">Send Test Notification</button>
            </form>
        </div>
    </div>
    
    <div class="mt-4 text-center">
        <a href="index.php" class="btn btn-secondary">Return to Dashboard</a>
    </div>';

echo '</div>
</body>
</html>';
?> 