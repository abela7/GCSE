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
                            <button class="btn btn-primary" onclick="requestNotificationPermission()">Enable Notifications!</button>
                            <button class="btn btn-warning" onclick="testAllNotifications()">Test All Notifications</button>
                            <button class="btn btn-danger" onclick="pauseAll()">Pause All</button>
                            <button class="btn btn-success" onclick="resumeAll()">Resume All</button>
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
const notificationConfig = {
    hourly: {
        title: "Time Check",
        body: "Make sure you are on track, keep doing all your tasks!",
        icon: "../assets/images/icon-192x192.png",
        tag: "hourly-reminder",
        enabled: true
    },
    morning: {
        title: "Good Morning Abela",
        body: "Make sure you stay productive today, Time is your only Precious Gift!",
        icon: "../assets/images/icon-192x192.png",
        tag: "morning-motivation",
        enabled: true
    },
    night: {
        title: "Good Night Reflection",
        body: "Tomorrow is a new Day! Thank GOD for everything, Do not forget to Study Bible.",
        icon: "../assets/images/icon-192x192.png",
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
    
    console.log(`[DEBUG] ${message}`);
}

// Clear debug console
function clearDebugConsole() {
    const debugConsole = document.getElementById('debugConsole');
    if (debugConsole) {
        debugConsole.innerHTML = '';
        debugLog('Debug console cleared', 'info');
    }
}

// Request notification permission
async function requestNotificationPermission() {
    try {
        debugLog('Requesting notification permission...', 'info');
        const permission = await Notification.requestPermission();
        
        if (permission === 'granted') {
            debugLog('Notification permission granted', 'success');
            await registerServiceWorker();
            showWelcomeNotification();
        } else {
            debugLog(`Notification permission ${permission}`, 'warning');
            alert('Please allow notifications to use this feature.');
        }
        
        updatePermissionStatus();
    } catch (error) {
        debugLog(`Error requesting permission: ${error}`, 'error');
        alert('Error requesting notification permission: ' + error.message);
    }
}

// Calculate next notification time
function calculateNextNotificationTime(type) {
    const now = new Date();
    let next = new Date(now);

    switch (type) {
        case 'hourly':
            // If current hour is before 21:00, set to 21:00
            if (now.getHours() < 21) {
                next.setHours(21, 0, 0, 0);
            } else {
                // If after 21:00, set to next hour
                next.setHours(next.getHours() + 1);
                next.setMinutes(0, 0, 0);
            }
            break;
        case 'morning':
            // Set to 7:00 AM
            next.setHours(7, 0, 0, 0);
            // If it's already past 7:00 AM, set to tomorrow
            if (now >= next) {
                next.setDate(next.getDate() + 1);
            }
            break;
        case 'night':
            // Set to midnight (00:00)
            next.setHours(24, 0, 0, 0);
            break;
    }
    return next;
}

// Update next notification display
function updateNextNotificationTime(type) {
    const nextElement = document.getElementById(`${type}Next`);
    if (!nextElement) return;

    const next = calculateNextNotificationTime(type);
    nextElement.textContent = next.toLocaleString();
}

// Check for scheduled notifications
function checkScheduledNotifications() {
    const now = new Date();
    const hour = now.getHours();
    const minute = now.getMinutes();

    debugLog(`Checking scheduled notifications at ${hour}:${minute}`, 'info');

    // Hourly notification (21:00 onwards)
    if (minute === 0 && hour >= 21 && notificationConfig.hourly.enabled) {
        debugLog('Triggering hourly notification', 'info');
        showNotification(
            notificationConfig.hourly.title,
            `${hour}:00 - ${notificationConfig.hourly.body}`,
            notificationConfig.hourly.tag
        );
    }

    // Morning notification (7:00)
    if (hour === 7 && minute === 0 && notificationConfig.morning.enabled) {
        debugLog('Triggering morning notification', 'info');
        showNotification(
            notificationConfig.morning.title,
            notificationConfig.morning.body,
            notificationConfig.morning.tag
        );
    }

    // Night notification (00:00)
    if (hour === 0 && minute === 0 && notificationConfig.night.enabled) {
        debugLog('Triggering night notification', 'info');
        showNotification(
            notificationConfig.night.title,
            notificationConfig.night.body,
            notificationConfig.night.tag
        );
    }

    // Update next notification times
    for (const type in notificationConfig) {
        updateNextNotificationTime(type);
    }
}

// Start notification scheduler
function startNotificationScheduler() {
    debugLog('Starting notification scheduler...', 'info');
    
    // Initial check and display
    checkScheduledNotifications();
    
    // Check every minute for notifications
    setInterval(checkScheduledNotifications, 60000);
    
    debugLog('Notification scheduler started successfully', 'success');
}

// Register service worker
async function registerServiceWorker() {
    try {
        debugLog('Registering service worker...', 'info');
        const registration = await navigator.serviceWorker.register('../service-worker.js', {
            scope: '../'
        });
        debugLog('Service worker registered successfully', 'success');
        
        // Start the notification scheduler after successful registration
        startNotificationScheduler();
        
        return registration;
    } catch (error) {
        debugLog(`Service worker registration failed: ${error}`, 'error');
        throw error;
    }
}

// Show welcome notification
async function showWelcomeNotification() {
    if (Notification.permission === 'granted') {
        try {
            await showNotification('Welcome!', 'Notifications have been enabled successfully.');
            debugLog('Welcome notification sent', 'success');
        } catch (error) {
            debugLog(`Error showing welcome notification: ${error}`, 'error');
        }
    }
}

// Show a notification
async function showNotification(title, message, tag = 'test') {
    if (Notification.permission !== 'granted') {
        debugLog('Cannot show notification - permission not granted', 'warning');
        return;
    }

    try {
        const registration = await navigator.serviceWorker.ready;
        await registration.showNotification(title, {
            body: message,
            icon: '../assets/images/icon-192x192.png',
            tag: tag,
            vibrate: [200, 100, 200],
            requireInteraction: true
        });
        debugLog(`Notification shown: ${title}`, 'success');
    } catch (error) {
        debugLog(`Error showing notification: ${error}`, 'error');
        throw error;
    }
}

// Test a specific notification
async function testActualNotification(type) {
    if (Notification.permission !== 'granted') {
        debugLog('Cannot test notification - permission not granted', 'warning');
        alert('Please enable notifications first');
        return;
    }

    try {
        const config = notificationConfig[type];
        if (!config) {
            throw new Error(`Invalid notification type: ${type}`);
        }

        await showNotification(config.title, config.body, config.tag);
        debugLog(`Test notification sent for ${type}`, 'success');
    } catch (error) {
        debugLog(`Error testing ${type} notification: ${error}`, 'error');
        alert(`Error testing ${type} notification: ${error.message}`);
    }
}

// Test all notifications
async function testAllNotifications() {
    if (Notification.permission !== 'granted') {
        debugLog('Cannot test notifications - permission not granted', 'warning');
        alert('Please enable notifications first');
        return;
    }

    try {
        for (const type in notificationConfig) {
            if (notificationConfig[type].enabled) {
                await testActualNotification(type);
                await new Promise(resolve => setTimeout(resolve, 1000));
            }
        }
        debugLog('All test notifications sent', 'success');
    } catch (error) {
        debugLog(`Error testing notifications: ${error}`, 'error');
    }
}

// Toggle notification state
function toggleNotification(type) {
    const config = notificationConfig[type];
    if (!config) {
        debugLog(`Invalid notification type: ${type}`, 'error');
        return;
    }

    config.enabled = !config.enabled;
    debugLog(`${type} notifications ${config.enabled ? 'enabled' : 'disabled'}`, 'info');
    updateNotificationStatus(type);
    saveNotificationStates();
}

// Pause all notifications
function pauseAll() {
    for (const type in notificationConfig) {
        notificationConfig[type].enabled = false;
        updateNotificationStatus(type);
    }
    saveNotificationStates();
    debugLog('All notifications paused', 'info');
}

// Resume all notifications
function resumeAll() {
    for (const type in notificationConfig) {
        notificationConfig[type].enabled = true;
        updateNotificationStatus(type);
    }
    saveNotificationStates();
    debugLog('All notifications resumed', 'info');
}

// Update notification status display
function updateNotificationStatus(type) {
    const statusBadge = document.getElementById(`${type}Status`);
    const toggleButton = document.getElementById(`${type}Toggle`);
    
    if (statusBadge && toggleButton) {
        const isEnabled = notificationConfig[type].enabled;
        statusBadge.className = `badge ${isEnabled ? 'bg-success' : 'bg-warning'} status-badge`;
        statusBadge.textContent = isEnabled ? 'Active' : 'Paused';
        toggleButton.textContent = isEnabled ? 'Pause' : 'Resume';
        toggleButton.className = `btn ${isEnabled ? 'btn-warning' : 'btn-success'}`;
        
        // Update next notification time when status changes
        updateNextNotificationTime(type);
    }
}

// Update permission status display
function updatePermissionStatus() {
    const statusElement = document.getElementById('permissionStatus');
    if (!statusElement) return;

    switch (Notification.permission) {
        case 'granted':
            statusElement.className = 'alert alert-success';
            statusElement.textContent = 'Notifications are enabled!';
            break;
        case 'denied':
            statusElement.className = 'alert alert-danger';
            statusElement.textContent = 'Notifications are blocked. Please enable them in your browser settings.';
            break;
        default:
            statusElement.className = 'alert alert-warning';
            statusElement.textContent = 'Notifications require permission.';
    }
}

// Save notification states
function saveNotificationStates() {
    const states = {};
    for (const type in notificationConfig) {
        states[type] = notificationConfig[type].enabled;
    }
    localStorage.setItem('notificationStates', JSON.stringify(states));
    debugLog('Notification states saved', 'info');
}

// Load notification states
function loadNotificationStates() {
    try {
        const states = JSON.parse(localStorage.getItem('notificationStates'));
        if (states) {
            for (const type in states) {
                if (notificationConfig[type]) {
                    notificationConfig[type].enabled = states[type];
                    updateNotificationStatus(type);
                }
            }
            debugLog('Notification states loaded', 'success');
        }
    } catch (error) {
        debugLog(`Error loading notification states: ${error}`, 'error');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    debugLog('Page loaded, initializing...', 'info');
    loadNotificationStates();
    updatePermissionStatus();
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?> 