<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$page_title = "Notification Settings";

// Include header first to ensure proper styling
require_once __DIR__ . '/includes/header.php';

// Initialize variables
$error = null;
$success = null;
$settings = [
    'task_reminders' => 1,
    'exam_reminders' => 1,
    'daily_motivation' => 1
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    // Include database connection
    require_once __DIR__ . '/includes/db_connect.php';
    
    // Check if notification_settings table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'notification_settings'")->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS notification_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            task_reminders TINYINT(1) DEFAULT 1,
            exam_reminders TINYINT(1) DEFAULT 1,
            daily_motivation TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        
        // Add foreign key constraint separately to avoid errors
        try {
            $pdo->exec("ALTER TABLE notification_settings ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
            $pdo->exec("ALTER TABLE notification_settings ADD UNIQUE KEY unique_user_settings (user_id)");
        } catch (PDOException $e) {
            // Ignore error if constraint already exists
        }
    }

    // Get current notification settings from database
    $stmt = $pdo->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no settings exist, create default settings
    if (!$settings) {
        $stmt = $pdo->prepare("INSERT INTO notification_settings (user_id, task_reminders, exam_reminders, daily_motivation) VALUES (?, 1, 1, 1)");
        $stmt->execute([$_SESSION['user_id']]);
        $settings = [
            'task_reminders' => 1,
            'exam_reminders' => 1,
            'daily_motivation' => 1
        ];
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_settings'])) {
            $task_reminders = isset($_POST['task_reminders']) ? 1 : 0;
            $exam_reminders = isset($_POST['exam_reminders']) ? 1 : 0;
            $daily_motivation = isset($_POST['daily_motivation']) ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE notification_settings SET 
                task_reminders = ?, 
                exam_reminders = ?, 
                daily_motivation = ? 
                WHERE user_id = ?");
            $stmt->execute([$task_reminders, $exam_reminders, $daily_motivation, $_SESSION['user_id']]);
            
            $success = "Notification settings updated successfully!";
            
            // Refresh settings after update
            $stmt = $pdo->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Notification Settings</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <h5>Error Details:</h5>
                            <p><?php echo $error; ?></p>
                            <p>Please check your database connection and table structure.</p>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="task_reminders" name="task_reminders" 
                                    <?php echo $settings['task_reminders'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="task_reminders">
                                    Task Reminders
                                </label>
                            </div>
                            <small class="text-muted">Get reminders for incomplete tasks</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="exam_reminders" name="exam_reminders" 
                                    <?php echo $settings['exam_reminders'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="exam_reminders">
                                    Exam Reminders
                                </label>
                            </div>
                            <small class="text-muted">Get reminders for upcoming exams</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="daily_motivation" name="daily_motivation" 
                                    <?php echo $settings['daily_motivation'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="daily_motivation">
                                    Daily Motivation
                                </label>
                            </div>
                            <small class="text-muted">Receive daily motivational messages</small>
                        </div>

                        <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                    </form>

                    <hr>

                    <h5 class="mb-3">Test Notifications</h5>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="testTaskNotification()">
                            Test Task Reminder
                        </button>
                        <button class="btn btn-outline-primary" onclick="testExamNotification()">
                            Test Exam Reminder
                        </button>
                        <button class="btn btn-outline-primary" onclick="testMotivationNotification()">
                            Test Motivation Message
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to request notification permission if not already granted
async function requestNotificationPermission() {
    if (!("Notification" in window)) {
        alert("This browser does not support desktop notification");
        return false;
    }

    if (Notification.permission === "granted") {
        return true;
    }

    const permission = await Notification.requestPermission();
    return permission === "granted";
}

// Test Task Notification
async function testTaskNotification() {
    if (!await requestNotificationPermission()) return;

    try {
        const response = await fetch('/api/get_incomplete_tasks.php');
        const data = await response.json();
        
        if (data.success && data.tasks.length > 0) {
            const task = data.tasks[0];
            new Notification("Test Task Reminder", {
                body: `You have an incomplete task: ${task.title}`,
                icon: '/assets/images/icon-192x192.png'
            });
        } else {
            new Notification("Test Task Reminder", {
                body: "You have no incomplete tasks",
                icon: '/assets/images/icon-192x192.png'
            });
        }
    } catch (error) {
        console.error('Error testing task notification:', error);
        alert('Error testing task notification. Please check console for details.');
    }
}

// Test Exam Notification
async function testExamNotification() {
    if (!await requestNotificationPermission()) return;

    try {
        const response = await fetch('/api/get_exam_countdown.php');
        const data = await response.json();
        
        if (data.success && data.exams.length > 0) {
            const exam = data.exams[0];
            new Notification("Test Exam Reminder", {
                body: `Upcoming exam: ${exam.subject} in ${exam.days_until} days`,
                icon: '/assets/images/icon-192x192.png'
            });
        } else {
            new Notification("Test Exam Reminder", {
                body: "No upcoming exams found",
                icon: '/assets/images/icon-192x192.png'
            });
        }
    } catch (error) {
        console.error('Error testing exam notification:', error);
        alert('Error testing exam notification. Please check console for details.');
    }
}

// Test Motivation Notification
async function testMotivationNotification() {
    if (!await requestNotificationPermission()) return;

    try {
        const response = await fetch('/api/get_motivational_message.php');
        const data = await response.json();
        
        if (data.success) {
            new Notification("Test Motivation Message", {
                body: data.message,
                icon: '/assets/images/icon-192x192.png'
            });
        } else {
            new Notification("Test Motivation Message", {
                body: "Keep going! You're doing great!",
                icon: '/assets/images/icon-192x192.png'
            });
        }
    } catch (error) {
        console.error('Error testing motivation notification:', error);
        alert('Error testing motivation notification. Please check console for details.');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?> 