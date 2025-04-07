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
// Add base path constant
const BASE_PATH = '/GCSE';

// Check if running on Android
const isAndroid = /Android/i.test(navigator.userAgent);
// Check if running as PWA
const isPWA = window.matchMedia('(display-mode: standalone)').matches;

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
        if (isAndroid) {
            if (isPWA) {
                statusDiv.innerHTML = 'Notifications are blocked. Please enable them in your device settings:<br>' +
                    '1. Long press the app icon<br>' +
                    '2. Tap App Info<br>' +
                    '3. Tap Notifications<br>' +
                    '4. Enable notifications';
            } else {
                statusDiv.innerHTML = 'For the best experience, please:<br>' +
                    '1. Install the app from the browser menu<br>' +
                    '2. Open the installed app<br>' +
                    '3. Enable notifications when prompted';
            }
        } else {
            statusDiv.textContent = 'Notifications are blocked. Please enable them in your browser settings.';
        }
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
            const registration = await registerServiceWorker();
            if (registration) {
                // Try to register periodic sync
                if ('periodicSync' in registration) {
                    try {
                        await registration.periodicSync.register('sync-notifications', {
                            minInterval: 60 * 60 * 1000 // Sync every hour
                        });
                        console.log('Periodic sync registered successfully');
                    } catch (error) {
                        console.log('Periodic sync not available:', error);
                        // Fallback to regular sync
                        try {
                            await registration.sync.register('sync-notifications');
                            console.log('Regular sync registered successfully');
                        } catch (syncError) {
                            console.log('Regular sync not available:', syncError);
                        }
                    }
                } else {
                    console.log('Periodic sync not supported');
                    // Fallback to regular sync
                    try {
                        await registration.sync.register('sync-notifications');
                        console.log('Regular sync registered successfully');
                    } catch (error) {
                        console.log('Regular sync not available:', error);
                    }
                }
            }
            return true;
        }
        return false;
    } catch (error) {
        console.error('Error requesting notification permission:', error);
        alert('Error requesting notification permission. Please try again.');
        return false;
    }
}

// Function to register service worker
async function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register(BASE_PATH + '/service-worker.js', {
                scope: BASE_PATH + '/'
            });
            console.log('ServiceWorker registration successful with scope:', registration.scope);
            return registration;
        } catch (error) {
            console.error('ServiceWorker registration failed:', error);
            if (isAndroid) {
                alert('Error registering service worker. Please try reinstalling the app.');
            }
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

// Test functions with proper error handling
async function showTestNotification(title, options) {
    try {
        const registration = await navigator.serviceWorker.ready;
        if (!registration.showNotification) {
            throw new Error('Notification API not supported');
        }
        
        // Add base path to icon URLs
        options.icon = BASE_PATH + options.icon;
        options.badge = BASE_PATH + options.badge;
        
        await registration.showNotification(title, options);
    } catch (error) {
        console.error('Error showing notification:', error);
        if (isAndroid) {
            if (isPWA) {
                alert('Error showing notification. Please check if notifications are enabled in your device settings.');
            } else {
                alert('For the best experience, please install the app from the browser menu and try again.');
            }
        } else {
            alert('Error showing notification. Please check if notifications are enabled.');
        }
    }
}

// Test Task Notification
async function testTaskNotification() {
    await showTestNotification("Test Task Reminder", {
        body: "This is a test task reminder",
        icon: '/assets/images/icon-192x192.png',
        badge: '/assets/images/icon-96x96.png',
        vibrate: [200, 100, 200, 100, 200],
        tag: 'task-reminder',
        renotify: true,
        actions: [
            {
                action: 'open',
                title: 'View Tasks'
            },
            {
                action: 'close',
                title: 'Close'
            }
        ],
        data: {
            url: BASE_PATH + '/pages/tasks/index.php'
        }
    });
}

// Test Exam Notification
async function testExamNotification() {
    await showTestNotification("Test Exam Reminder", {
        body: "This is a test exam reminder",
        icon: '/assets/images/icon-192x192.png',
        badge: '/assets/images/icon-96x96.png',
        vibrate: [200, 100, 200, 100, 200],
        tag: 'exam-reminder',
        renotify: true,
        actions: [
            {
                action: 'open',
                title: 'View Exams'
            },
            {
                action: 'close',
                title: 'Close'
            }
        ],
        data: {
            url: BASE_PATH + '/pages/exam_countdown.php'
        }
    });
}

// Test Motivation Notification
async function testMotivationNotification() {
    try {
        const response = await fetch(BASE_PATH + '/api/get_motivational_message.php');
        if (!response.ok) throw new Error('Failed to fetch motivation message');
        const data = await response.json();
        
        await showTestNotification("Test Motivation Message", {
            body: data.success ? data.message : "Keep going! You're doing great!",
            icon: '/assets/images/icon-192x192.png',
            badge: '/assets/images/icon-96x96.png',
            vibrate: [200, 100, 200, 100, 200],
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
        alert('Error fetching motivation message. Please try again.');
    }
}

// Call initialize on page load
initializeNotifications();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?> 