// Function to request notification permission
function requestNotificationPermission() {
    if (!('Notification' in window)) {
        console.log('This browser does not support notifications');
        return;
    }

    if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
        Notification.requestPermission().then(function (permission) {
            if (permission === 'granted') {
                console.log('Notification permission granted');
                startProductivityReminder();
            }
        });
    } else if (Notification.permission === 'granted') {
        startProductivityReminder();
    }
}

// Function to send a notification
function sendNotification(title, options = {}) {
    if (!('Notification' in window)) {
        console.log('This browser does not support notifications');
        return;
    }

    if (Notification.permission === 'granted') {
        const defaultOptions = {
            icon: '/assets/favicon/favicon-32x32.png',
            badge: '/assets/favicon/favicon-32x32.png',
            vibrate: [200, 100, 200],
            tag: 'gcse-notification'
        };

        // Merge default options with provided options
        const finalOptions = { ...defaultOptions, ...options };
        
        // Create and show the notification
        const notification = new Notification(title, finalOptions);

        // Handle notification click
        notification.onclick = function(event) {
            event.preventDefault();
            window.focus();
            notification.close();
        };
    } else if (Notification.permission !== 'denied') {
        Notification.requestPermission().then(function (permission) {
            if (permission === 'granted') {
                sendNotification(title, options);
            }
        });
    }
}

// Function to start the productivity reminder
function startProductivityReminder() {
    // Send first notification immediately
    sendProductivityReminder();
    
    // Set up interval for subsequent notifications
    setInterval(sendProductivityReminder, 60000); // 60000 ms = 1 minute
}

// Function to send the productivity reminder
function sendProductivityReminder() {
    sendNotification('Productivity Reminder', {
        body: 'Hey Abela, I hope u are doing something productive!',
        requireInteraction: true // This makes the notification stay until user interacts with it
    });
}

// Request permission and start notifications when the script loads
document.addEventListener('DOMContentLoaded', function() {
    requestNotificationPermission();
}); 