// Function to request notification permission
function requestNotificationPermission() {
    console.log('Checking notification support...');
    
    if (!('Notification' in window)) {
        console.log('This browser does not support notifications');
        return;
    }

    console.log('Current notification permission:', Notification.permission);

    if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
        console.log('Requesting notification permission...');
        Notification.requestPermission().then(function (permission) {
            console.log('Permission response:', permission);
            if (permission === 'granted') {
                console.log('Permission granted, starting reminder...');
                startProductivityReminder();
            }
        });
    } else if (Notification.permission === 'granted') {
        console.log('Permission already granted, starting reminder...');
        startProductivityReminder();
    }
}

// Function to send a notification
function sendNotification(title, options = {}) {
    console.log('Attempting to send notification:', title);
    
    if (!('Notification' in window)) {
        console.log('This browser does not support notifications');
        return;
    }

    console.log('Notification permission status:', Notification.permission);

    if (Notification.permission === 'granted') {
        const defaultOptions = {
            icon: '/assets/favicon/favicon-32x32.png',
            badge: '/assets/favicon/favicon-32x32.png',
            vibrate: [200, 100, 200],
            tag: 'gcse-notification'
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
                notification.close();
            };
        } catch (error) {
            console.error('Error sending notification:', error);
        }
    } else if (Notification.permission !== 'denied') {
        console.log('Permission not granted, requesting permission...');
        Notification.requestPermission().then(function (permission) {
            console.log('Permission response:', permission);
            if (permission === 'granted') {
                sendNotification(title, options);
            }
        });
    }
}

// Function to start the productivity reminder
function startProductivityReminder() {
    console.log('Starting productivity reminder...');
    // Send first notification immediately
    sendProductivityReminder();
    
    // Set up interval for subsequent notifications
    console.log('Setting up interval for notifications...');
    setInterval(sendProductivityReminder, 60000); // 60000 ms = 1 minute
}

// Function to send the productivity reminder
function sendProductivityReminder() {
    console.log('Sending productivity reminder...');
    sendNotification('Productivity Reminder', {
        body: 'Hey Abela, I hope u are doing something productive!',
        requireInteraction: true // This makes the notification stay until user interacts with it
    });
}

// Request permission and start notifications when the script loads
console.log('Setting up notification system...');
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, requesting notification permission...');
    requestNotificationPermission();
}); 