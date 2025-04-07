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
                    <div id="notificationStatus" class="alert alert-info d-none">
                        Checking notification permission...
                    </div>

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
                    <div id="permissionButton" class="mb-3 d-none">
                        <button class="btn btn-warning w-100" onclick="requestNotificationPermission()">
                            Enable Notifications
                        </button>
                    </div>

                    <div class="d-grid gap-2" id="testButtons">
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
// Function to update UI based on notification permission
function updateNotificationUI(permission) {
    const statusDiv = document.getElementById('notificationStatus');
    const permissionButton = document.getElementById('permissionButton');
    const testButtons = document.getElementById('testButtons');

    statusDiv.classList.remove('d-none');
    
    if (permission === 'granted') {
        statusDiv.className = 'alert alert-success';
        statusDiv.textContent = 'Notifications are enabled!';
        permissionButton.classList.add('d-none');
        testButtons.classList.remove('d-none');
    } else if (permission === 'denied') {
        statusDiv.className = 'alert alert-danger';
        statusDiv.textContent = 'Notifications are blocked. Please enable them in your browser settings.';
        permissionButton.classList.add('d-none');
        testButtons.classList.add('d-none');
    } else {
        statusDiv.className = 'alert alert-warning';
        statusDiv.textContent = 'Notifications require permission to work.';
        permissionButton.classList.remove('d-none');
        testButtons.classList.add('d-none');
    }
}

// Function to request notification permission
async function requestNotificationPermission() {
    if (!("Notification" in window)) {
        alert("This browser does not support notifications");
        return false;
    }

    try {
        const permission = await Notification.requestPermission();
        updateNotificationUI(permission);
        
        if (permission === "granted") {
            await registerServiceWorker();
            return true;
        }
        return false;
    } catch (error) {
        console.error('Error requesting notification permission:', error);
        return false;
    }
}

// Function to register service worker
async function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register('/service-worker.js', {
                scope: '/'
            });
            console.log('ServiceWorker registration successful');

            // Register for periodic sync if supported
            if ('periodicSync' in registration) {
                try {
                    await registration.periodicSync.register('sync-notifications', {
                        minInterval: 60 * 60 * 1000 // Sync every hour
                    });
                    console.log('Periodic background sync registered');
                } catch (error) {
                    console.log('Periodic background sync registration failed:', error);
                }
            }

            return registration;
        } catch (error) {
            console.error('ServiceWorker registration failed:', error);
            return null;
        }
    }
    return null;
}

// Initialize notifications
async function initializeNotifications() {
    if ("Notification" in window) {
        updateNotificationUI(Notification.permission);
        if (Notification.permission === "granted") {
            await registerServiceWorker();
        }
    } else {
        document.getElementById('notificationStatus').className = 'alert alert-danger';
        document.getElementById('notificationStatus').textContent = 'Your browser does not support notifications';
        document.getElementById('testButtons').classList.add('d-none');
    }
}

// Call initialize on page load
initializeNotifications();

// Test Task Notification
async function testTaskNotification() {
    try {
        const registration = await navigator.serviceWorker.ready;
        await registration.showNotification("Test Task Reminder", {
            body: "This is a test task reminder",
            icon: '/assets/images/icon-192x192.png',
            badge: '/assets/images/icon-192x192.png',
            vibrate: [200, 100, 200],
            tag: 'task-reminder',
            renotify: true,
            actions: [
                {
                    action: 'explore',
                    title: 'View Tasks'
                },
                {
                    action: 'close',
                    title: 'Close'
                }
            ]
        });
    } catch (error) {
        console.error('Error testing task notification:', error);
        alert('Error showing notification. Please check if notifications are enabled.');
    }
}

// Test Exam Notification
async function testExamNotification() {
    try {
        const registration = await navigator.serviceWorker.ready;
        await registration.showNotification("Test Exam Reminder", {
            body: "This is a test exam reminder",
            icon: '/assets/images/icon-192x192.png',
            badge: '/assets/images/icon-192x192.png',
            vibrate: [200, 100, 200],
            tag: 'exam-reminder',
            renotify: true,
            actions: [
                {
                    action: 'explore',
                    title: 'View Exams'
                },
                {
                    action: 'close',
                    title: 'Close'
                }
            ]
        });
    } catch (error) {
        console.error('Error testing exam notification:', error);
        alert('Error showing notification. Please check if notifications are enabled.');
    }
}

// Test Motivation Notification
async function testMotivationNotification() {
    try {
        const response = await fetch('/api/get_motivational_message.php');
        const data = await response.json();
        
        const registration = await navigator.serviceWorker.ready;
        await registration.showNotification("Test Motivation Message", {
            body: data.success ? data.message : "Keep going! You're doing great!",
            icon: '/assets/images/icon-192x192.png',
            badge: '/assets/images/icon-192x192.png',
            vibrate: [200, 100, 200],
            tag: 'motivation',
            renotify: true,
            actions: [
                {
                    action: 'close',
                    title: 'Thanks!'
                }
            ]
        });
    } catch (error) {
        console.error('Error testing motivation notification:', error);
        alert('Error showing notification. Please check if notifications are enabled.');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?> 