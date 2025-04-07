<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$page_title = "Notification Settings";

// Initialize default settings
$default_settings = [
    'task_reminders' => 1,
    'exam_reminders' => 1,
    'daily_motivation' => 1
];

// Get current settings from cookie or use defaults
$settings = [];
foreach ($default_settings as $key => $value) {
    $settings[$key] = isset($_COOKIE[$key]) ? $_COOKIE[$key] : $value;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    foreach ($default_settings as $key => $value) {
        $new_value = isset($_POST[$key]) ? 1 : 0;
        setcookie($key, $new_value, time() + (86400 * 30), "/"); // Cookie expires in 30 days
        $settings[$key] = $new_value;
    }
    $success = "Notification settings updated successfully!";
}

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Notification Settings</h4>
                </div>
                <div class="card-body">
                    <!-- Status Display -->
                    <div id="notificationStatus" class="alert alert-info mb-4">
                        Checking notification status...
                    </div>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <!-- Settings Form -->
                    <form id="notificationForm" class="mb-4">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="taskNotifications" name="taskNotifications">
                                <label class="form-check-label" for="taskNotifications">
                                    Task Reminders
                                </label>
                            </div>
                            <small class="text-muted">Get reminders for incomplete tasks</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="examNotifications" name="examNotifications">
                                <label class="form-check-label" for="examNotifications">
                                    Exam Reminders
                                </label>
                            </div>
                            <small class="text-muted">Get reminders for upcoming exams</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="motivationalNotifications" name="motivationalNotifications">
                                <label class="form-check-label" for="motivationalNotifications">
                                    Daily Motivation
                                </label>
                            </div>
                            <small class="text-muted">Receive daily motivational messages</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>

                    <!-- Permission Request -->
                    <div id="permissionRequest" class="d-none">
                        <div class="alert alert-warning">
                            <h5 class="alert-heading">Enable Notifications</h5>
                            <p>To receive notifications, please allow them when prompted.</p>
                            <button class="btn btn-warning" onclick="requestNotificationPermission()">
                                Enable Notifications
                            </button>
                        </div>
                    </div>

                    <!-- Test Notifications -->
                    <div id="testNotifications" class="d-none">
                        <h5 class="mb-3">Test Notifications</h5>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="testTaskNotification()">
                                Test Task Reminder
                            </button>
                            <button class="btn btn-outline-primary" onclick="testExamNotification()">
                                Test Exam Reminder
                            </button>
                            <button class="btn btn-outline-primary" onclick="testMotivationalNotification()">
                                Test Motivation Message
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update UI based on notification status
async function updateUI() {
    const statusDiv = document.getElementById('notificationStatus');
    const permissionDiv = document.getElementById('permissionRequest');
    const testDiv = document.getElementById('testNotifications');
    
    if (!window.notifications) {
        statusDiv.className = 'alert alert-danger';
        statusDiv.textContent = 'Notifications are not supported in this browser';
        return;
    }

    const permission = await window.notifications.checkPermission();
    
    switch (permission) {
        case 'granted':
            statusDiv.className = 'alert alert-success';
            statusDiv.textContent = 'Notifications are enabled';
            permissionDiv.classList.add('d-none');
            testDiv.classList.remove('d-none');
            break;
            
        case 'denied':
            statusDiv.className = 'alert alert-danger';
            statusDiv.innerHTML = window.notifications.isAndroid ? 
                'Notifications are blocked. Please enable them in your device settings:<br>' +
                '1. Go to Settings > Apps<br>' +
                '2. Find your browser app<br>' +
                '3. Tap Notifications<br>' +
                '4. Enable notifications' :
                'Notifications are blocked. Please enable them in your browser settings.';
            permissionDiv.classList.add('d-none');
            testDiv.classList.add('d-none');
            break;
            
        default:
            statusDiv.className = 'alert alert-warning';
            statusDiv.textContent = 'Notifications require permission';
            permissionDiv.classList.remove('d-none');
            testDiv.classList.add('d-none');
    }
}

// Request notification permission
async function requestNotificationPermission() {
    const permission = await window.notifications.requestPermission();
    updateUI();
    return permission === 'granted';
}

// Test notification functions
async function testTaskNotification() {
    await window.notifications.testTaskNotification();
}

async function testExamNotification() {
    await window.notifications.testExamNotification();
}

async function testMotivationalNotification() {
    await window.notifications.testMotivationalNotification();
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
    // Initialize notifications
    if (window.notifications) {
        window.notifications.init().then(() => {
            updateUI();
        });
    } else {
        console.error('Notifications module not loaded');
        updateUI();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?> 