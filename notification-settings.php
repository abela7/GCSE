<?php
require_once 'includes/header.php';
require_once 'includes/navbar.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
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
    }
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
}

// Test Exam Notification
async function testExamNotification() {
    if (!await requestNotificationPermission()) return;

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
}

// Test Motivation Notification
async function testMotivationNotification() {
    if (!await requestNotificationPermission()) return;

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
}
</script>

<?php require_once 'includes/footer.php'; ?> 