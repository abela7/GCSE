// Function to request notification permission
function requestNotificationPermission() {
    console.log('Checking notification support...');
    
    // Check if running in a secure context
    if (!window.isSecureContext) {
        console.error('Notifications require a secure context (HTTPS)');
        return;
    }

    // Check if notifications are supported
    if (!('Notification' in window)) {
        console.error('This browser does not support notifications');
        return;
    }

    // Check if service worker is supported (optional but recommended)
    if (!('serviceWorker' in navigator)) {
        console.warn('Service Worker is not supported - notifications might be limited');
    }

    console.log('Current notification permission:', Notification.permission);

    // If permission is denied, inform the user how to enable it
    if (Notification.permission === 'denied') {
        console.log('Notifications are blocked. Please enable them in your browser settings.');
        alert('Notifications are blocked. To receive notifications, please enable them in your browser settings by clicking the lock icon in the address bar.');
        return;
    }

    if (Notification.permission !== 'granted') {
        console.log('Requesting notification permission...');
        try {
            Notification.requestPermission().then(function (permission) {
                console.log('Permission response:', permission);
                if (permission === 'granted') {
                    console.log('Permission granted, starting reminder...');
                    startProductivityReminder();
                } else {
                    console.log('Permission not granted:', permission);
                }
            }).catch(function(error) {
                console.error('Error requesting permission:', error);
            });
        } catch (error) {
            console.error('Error in permission request:', error);
        }
    } else {
        console.log('Permission already granted, starting reminder...');
        startProductivityReminder();
    }
}

// Function to send a notification
function sendNotification(title, options = {}) {
    console.log('Attempting to send notification:', title);
    
    if (!window.isSecureContext) {
        console.error('Cannot send notification - requires secure context (HTTPS)');
        return;
    }

    if (!('Notification' in window)) {
        console.error('Cannot send notification - browser does not support notifications');
        return;
    }

    console.log('Notification permission status:', Notification.permission);

    if (Notification.permission === 'granted') {
        const defaultOptions = {
            icon: '/assets/favicon/favicon-32x32.png',
            badge: '/assets/favicon/favicon-32x32.png',
            vibrate: [200, 100, 200],
            tag: 'gcse-notification',
            renotify: true // Allow new notifications even if one is already shown
        };

        // Merge default options with provided options
        const finalOptions = { ...defaultOptions, ...options };
        console.log('Sending notification with options:', finalOptions);
        
        try {
            // Create and show the notification
            const notification = new Notification(title, finalOptions);
            console.log('Notification sent successfully');

            // Handle notification click
            notification.onclick = function(event) {
                console.log('Notification clicked');
                event.preventDefault();
                window.focus();
                if (options.onClick) {
                    options.onClick();
                }
                notification.close();
            };

            // Handle notification error
            notification.onerror = function(error) {
                console.error('Notification error:', error);
            };
        } catch (error) {
            console.error('Error sending notification:', error);
            // Try alternative method if available
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({
                    type: 'notification',
                    title: title,
                    options: finalOptions
                });
            }
        }
    } else {
        console.log('Requesting permission as it was not granted...');
        requestNotificationPermission();
    }
}

// Function to start the productivity reminder
function startProductivityReminder() {
    console.log('Starting productivity reminder...');
    // Send first notification immediately
    sendProductivityReminder();
    
    // Set up interval for subsequent notifications
    console.log('Setting up interval for notifications...');
    const intervalId = setInterval(sendProductivityReminder, 60000); // 60000 ms = 1 minute
    
    // Store interval ID to allow stopping notifications later
    window.productivityReminderId = intervalId;
}

// Function to stop productivity reminders
function stopProductivityReminder() {
    if (window.productivityReminderId) {
        clearInterval(window.productivityReminderId);
        console.log('Productivity reminders stopped');
    }
}

// Function to send the productivity reminder
function sendProductivityReminder() {
    console.log('Sending productivity reminder...');
    sendNotification('Productivity Reminder', {
        body: 'Hey Abela, I hope u are doing something productive!',
        requireInteraction: true, // This makes the notification stay until user interacts with it
        timestamp: Date.now(), // Add timestamp to ensure uniqueness
        tag: 'productivity-reminder-' + Date.now() // Unique tag for each notification
    });
}

// Request permission and start notifications when the script loads
console.log('Setting up notification system...');
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, requesting notification permission...');
    // Small delay to ensure everything is loaded
    setTimeout(requestNotificationPermission, 1000);
}); 