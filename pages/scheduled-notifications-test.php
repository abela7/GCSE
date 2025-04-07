<?php
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Scheduled Notifications Test</h2>
                </div>
                <div class="card-body">
                    <!-- Permission Status -->
                    <div class="alert alert-info" id="permissionStatus">
                        Checking notification permission...
                    </div>

                    <!-- Main Controls -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Main Controls</h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary" onclick="requestPermission()">Enable Notifications!</button>
                            <button class="btn btn-warning" onclick="testAllNotifications()">Test All Notifications</button>
                            <button class="btn btn-danger" onclick="pauseAllNotifications()">Pause All</button>
                            <button class="btn btn-success" onclick="resumeAllNotifications()">Resume All</button>
                        </div>
                    </div>

                    <!-- Hourly Reminder -->
                    <div class="card notification-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Hourly Reminder (21:00 onwards)</h5>
                            <span class="badge bg-secondary status-badge" id="hourlyStatus">Checking...</span>
                        </div>
                        <div class="card-body">
                            <p>Reminds you every hour to stay on track with your tasks.</p>
                            <p><strong>Next notification:</strong> <span id="hourlyNext">Calculating...</span></p>
                            <button class="btn btn-primary" onclick="testActualNotification('hourly')">Test Hourly Notification</button>
                            <button class="btn btn-warning" onclick="toggleNotification('hourly')" id="hourlyToggle">Pause</button>
                        </div>
                    </div>

                    <!-- Morning Motivation -->
                    <div class="card notification-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Morning Motivation (7:00)</h5>
                            <span class="badge bg-secondary status-badge" id="morningStatus">Checking...</span>
                        </div>
                        <div class="card-body">
                            <p>Daily morning motivation message.</p>
                            <p><strong>Next notification:</strong> <span id="morningNext">Calculating...</span></p>
                            <button class="btn btn-primary" onclick="testActualNotification('morning')">Test Morning Notification</button>
                            <button class="btn btn-warning" onclick="toggleNotification('morning')" id="morningToggle">Pause</button>
                        </div>
                    </div>

                    <!-- Night Reminder -->
                    <div class="card notification-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Night Reminder (00:00)</h5>
                            <span class="badge bg-secondary status-badge" id="nightStatus">Checking...</span>
                        </div>
                        <div class="card-body">
                            <p>Daily night reminder for Bible study.</p>
                            <p><strong>Next notification:</strong> <span id="nightNext">Calculating...</span></p>
                            <button class="btn btn-primary" onclick="testActualNotification('night')">Test Night Notification</button>
                            <button class="btn btn-warning" onclick="toggleNotification('night')" id="nightToggle">Pause</button>
                        </div>
                    </div>

                    <!-- Debug Console -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Debug Console</h5>
                        </div>
                        <div class="card-body">
                            <div id="debugConsole" style="background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto; font-family: monospace;">
                            </div>
                            <button class="btn btn-secondary mt-2" onclick="clearDebugConsole()">Clear Debug Log</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Notification configurations
const notifications = {
    hourly: {
        title: "Time Check",
        body: "Make sure you are on track, keep doing all your tasks!",
        icon: "/assets/images/icon-192x192.png",
        tag: "hourly-reminder",
        enabled: true
    },
    morning: {
        title: "Good Morning Abela",
        body: "Make sure you stay productive today, Time is your only Precious Gift!",
        icon: "/assets/images/icon-192x192.png",
        tag: "morning-motivation",
        enabled: true
    },
    night: {
        title: "Good Night Reflection",
        body: "Tomorrow is a new Day! Thank GOD for everything, Do not forget to Study Bible.",
        icon: "/assets/images/icon-192x192.png",
        tag: "night-reminder",
        enabled: true
    }
};

// Debug logging function
function debugLog(message, type = 'info') {
    const debugConsole = document.getElementById('debugConsole');
    const timestamp = new Date().toLocaleTimeString();
    const colors = {
        info: 'text-info',
        success: 'text-success',
        warning: 'text-warning',
        error: 'text-danger'
    };
    
    const logEntry = document.createElement('div');
    logEntry.className = colors[type];
    logEntry.textContent = `[${timestamp}] ${message}`;
    debugConsole.appendChild(logEntry);
    debugConsole.scrollTop = debugConsole.scrollHeight;
    
    // Also log to browser console
    console.log(`[DEBUG] ${message}`);
}

function clearDebugConsole() {
    document.getElementById('debugConsole').innerHTML = '';
    debugLog('Debug console cleared', 'info');
}

// Initialize notification system
async function initializeNotifications() {
    debugLog('Initializing notifications...', 'info');
    updatePermissionStatus();
    
    if (Notification.permission === 'granted') {
        debugLog('Notification permission already granted', 'success');
        await registerServiceWorker();
        updateAllStatuses();
        startNotificationScheduler();
    } else {
        debugLog('Notification permission not granted', 'warning');
    }
}

// Update permission status display
function updatePermissionStatus() {
    const status = document.getElementById('permissionStatus');
    switch (Notification.permission) {
        case 'granted':
            status.className = 'alert alert-success';
            status.textContent = 'Notifications are enabled!';
            break;
        case 'denied':
            status.className = 'alert alert-danger';
            status.textContent = 'Notifications are blocked. Please enable them in your browser settings.';
            break;
        default:
            status.className = 'alert alert-warning';
            status.textContent = 'Notifications require permission.';
    }
}

// Register service worker
async function registerServiceWorker() {
    try {
        debugLog('Registering service worker...', 'info');
        const registration = await navigator.serviceWorker.register('/service-worker.js', {
            scope: '/'
        });
        debugLog('ServiceWorker registered successfully', 'success');
        return registration;
    } catch (error) {
        debugLog(`ServiceWorker registration failed: ${error}`, 'error');
        throw error;
    }
}

// Request notification permission
async function requestPermission() {
    try {
        debugLog('Requesting notification permission...', 'info');
        const permission = await Notification.requestPermission();
        updatePermissionStatus();
        
        if (permission === 'granted') {
            debugLog('Notification permission granted', 'success');
            await initializeNotifications();
            showNotification('Welcome', 'Notifications have been enabled!');
        } else {
            debugLog(`Notification permission ${permission}`, 'warning');
        }
    } catch (error) {
        debugLog(`Error requesting permission: ${error}`, 'error');
        alert('Error requesting notification permission: ' + error.message);
    }
}

// Show a notification
async function showNotification(title, body, tag = 'test') {
    if (Notification.permission !== 'granted') return;

    const registration = await navigator.serviceWorker.ready;
    await registration.showNotification(title, {
        body: body,
        icon: '/assets/images/icon-192x192.png',
        tag: tag,
        vibrate: [200, 100, 200],
        requireInteraction: true
    });
}

// Test actual notification
async function testActualNotification(type) {
    if (Notification.permission !== 'granted') {
        debugLog('Cannot test notification - permission not granted', 'warning');
        alert('Please enable notifications first');
        return;
    }

    try {
        debugLog(`Testing ${type} notification...`, 'info');
        const registration = await navigator.serviceWorker.ready;
        
        registration.active.postMessage({
            type: 'NOTIFICATION_STATES',
            states: {
                hourly: type === 'hourly',
                morning: type === 'morning',
                night: type === 'night'
            },
            isTest: true
        });
        debugLog(`Test message sent to service worker for ${type}`, 'success');
    } catch (error) {
        debugLog(`Error testing notification: ${error}`, 'error');
    }
}

// Test all notifications
async function testAllNotifications() {
    for (const type in notifications) {
        await testActualNotification(type);
        await new Promise(resolve => setTimeout(resolve, 1000)); // Wait 1 second between notifications
    }
}

// Toggle notification state
function toggleNotification(type) {
    const config = notifications[type];
    if (!config) {
        debugLog(`Invalid notification type: ${type}`, 'error');
        return;
    }

    config.enabled = !config.enabled;
    debugLog(`${type} notifications ${config.enabled ? 'enabled' : 'disabled'}`, 'info');
    updateStatus(type);
    saveNotificationStates();
}

// Update status display for a notification type
function updateStatus(type) {
    const config = notifications[type];
    if (!config) return;

    const statusElement = document.getElementById(`${type}Status`);
    const toggleButton = document.getElementById(`${type}Toggle`);
    const nextElement = document.getElementById(`${type}Next`);

    if (config.enabled) {
        statusElement.className = 'badge bg-success status-badge';
        statusElement.textContent = 'Active';
        toggleButton.textContent = 'Pause';
        toggleButton.className = 'btn btn-warning';
        updateNextNotificationTime(type);
    } else {
        statusElement.className = 'badge bg-warning status-badge';
        statusElement.textContent = 'Paused';
        toggleButton.textContent = 'Resume';
        toggleButton.className = 'btn btn-success';
        nextElement.textContent = 'Paused';
    }
}

// Update all notification statuses
function updateAllStatuses() {
    for (const type in notifications) {
        updateStatus(type);
    }
}

// Calculate and display next notification time
function updateNextNotificationTime(type) {
    const nextElement = document.getElementById(`${type}Next`);
    const now = new Date();
    let next;

    switch (type) {
        case 'hourly':
            next = new Date(now);
            if (now.getHours() < 21) {
                next.setHours(21, 0, 0, 0);
            } else {
                next.setHours(next.getHours() + 1);
                next.setMinutes(0, 0, 0);
            }
            break;
        case 'morning':
            next = new Date(now);
            next.setHours(7, 0, 0, 0);
            if (now >= next) {
                next.setDate(next.getDate() + 1);
            }
            break;
        case 'night':
            next = new Date(now);
            next.setHours(24, 0, 0, 0);
            break;
    }

    nextElement.textContent = next.toLocaleString();
}

// Save notification states to localStorage
function saveNotificationStates() {
    const states = {};
    for (const type in notifications) {
        states[type] = notifications[type].enabled;
    }
    localStorage.setItem('notificationStates', JSON.stringify(states));
}

// Load notification states from localStorage
function loadNotificationStates() {
    try {
        const states = JSON.parse(localStorage.getItem('notificationStates'));
        if (states) {
            for (const type in states) {
                if (notifications[type]) {
                    notifications[type].enabled = states[type];
                }
            }
        }
    } catch (error) {
        console.error('Error loading notification states:', error);
    }
}

// Pause all notifications
function pauseAllNotifications() {
    for (const type in notifications) {
        notifications[type].enabled = false;
    }
    updateAllStatuses();
    saveNotificationStates();
}

// Resume all notifications
function resumeAllNotifications() {
    for (const type in notifications) {
        notifications[type].enabled = true;
    }
    updateAllStatuses();
    saveNotificationStates();
}

// Start the notification scheduler
function startNotificationScheduler() {
    // Update next notification times every minute
    setInterval(() => {
        for (const type in notifications) {
            if (notifications[type].enabled) {
                updateNextNotificationTime(type);
            }
        }
    }, 60000);

    // Check for notifications every minute
    setInterval(checkScheduledNotifications, 60000);
}

// Check if any notifications should be shown
function checkScheduledNotifications() {
    const now = new Date();
    const hour = now.getHours();
    const minute = now.getMinutes();

    debugLog(`Checking scheduled notifications at ${hour}:${minute}`, 'info');

    // Hourly notification (21:00 onwards)
    if (minute === 0 && hour >= 21 && notifications.hourly.enabled) {
        debugLog('Triggering hourly notification', 'info');
        showNotification(
            notifications.hourly.title,
            `${hour}:00 - ${notifications.hourly.body}`,
            notifications.hourly.tag
        );
    }

    // Morning notification (7:00)
    if (hour === 7 && minute === 0 && notifications.morning.enabled) {
        debugLog('Triggering morning notification', 'info');
        showNotification(
            notifications.morning.title,
            notifications.morning.body,
            notifications.morning.tag
        );
    }

    // Night notification (00:00)
    if (hour === 0 && minute === 0 && notifications.night.enabled) {
        debugLog('Triggering night notification', 'info');
        showNotification(
            notifications.night.title,
            notifications.night.body,
            notifications.night.tag
        );
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    debugLog('Page loaded, initializing notification system...', 'info');
    loadNotificationStates();
    initializeNotifications();
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?> 