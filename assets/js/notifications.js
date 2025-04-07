// Function to register service worker
async function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            // Use absolute path with origin to ensure correct path on all devices
            const swPath = `${window.location.origin}/assets/js/service-worker.js`;
            console.log('Attempting to register service worker at:', swPath);
            const registration = await navigator.serviceWorker.register(swPath, {scope: '/'});
            console.log('Service Worker registered:', registration);
            return registration;
        } catch (error) {
            console.error('Service Worker registration failed:', error);
            return null;
        }
    }
    console.warn('Service Worker not supported in this browser');
    return null;
}

// Function to check if device is mobile
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Function to request notification permission
async function requestNotificationPermission() {
    console.log('Checking notification support...');
    const isMobile = isMobileDevice();
    console.log('Device type:', isMobile ? 'Mobile' : 'Desktop');
    
    // Check if running in a secure context
    if (!window.isSecureContext) {
        console.error('Notifications require a secure context (HTTPS)');
        alert('This site needs to run on HTTPS to enable notifications. Please access the site via HTTPS.');
        return;
    }

    // Register service worker first
    const swRegistration = await registerServiceWorker();
    
    // Check if notifications are supported
    if (!('Notification' in window)) {
        console.error('This browser does not support notifications');
        if (isMobileDevice()) {
            alert('Notifications might not be supported on your mobile browser. For best experience, please ensure notifications are enabled in your browser settings!.');
        }
        return;
    }

    console.log('Current notification permission:', Notification.permission);

    // If permission is denied, inform the user how to enable it
    if (Notification.permission === 'denied') {
        console.log('Notifications are blocked. Please enable them in your browser settings.');
        const message = isMobileDevice() 
            ? 'Notifications are blocked. To receive notifications, please enable them in your browser settings. On mobile, you might need to enable notifications in your system settings as well.'
            : 'Notifications are blocked. To receive notifications, please enable them in your browser settings by clicking the lock icon in the address bar.';
        alert(message);
        return;
    }

    if (Notification.permission !== 'granted') {
        console.log('Requesting notification permission...');
        try {
            const permission = await Notification.requestPermission();
            console.log('Permission response:', permission);
            if (permission === 'granted') {
                console.log('Permission granted, starting reminder...');
                startProductivityReminder();
            } else {
                console.log('Permission not granted:', permission);
                if (isMobileDevice()) {
                    alert('Please make sure notifications are enabled in both your browser and system settings.');
                }
            }
        } catch (error) {
            console.error('Error in permission request:', error);
            if (isMobileDevice()) {
                alert('There was an error enabling notifications. Please check your browser and system settings.');
            }
        }
    } else {
        console.log('Permission already granted, starting reminder...');
        startProductivityReminder();
    }
}

// Function to send a notification
async function sendNotification(title, options = {}) {
    console.log('Attempting to send notification:', title);
    
    if (!window.isSecureContext) {
        console.error('Cannot send notification - requires secure context (HTTPS)');
        return;
    }

    try {
        const swRegistration = await navigator.serviceWorker.ready;
        console.log('Service worker ready for notifications:', swRegistration);
        
        if (Notification.permission === 'granted') {
            const defaultOptions = {
                icon: `${window.location.origin}/assets/favicon/favicon-32x32.png`,
                badge: `${window.location.origin}/assets/favicon/favicon-32x32.png`,
                vibrate: [200, 100, 200],
                tag: 'gcse-notification-' + Date.now(),
                renotify: true,
                silent: false,
                data: {
                    timestamp: Date.now(),
                    url: window.location.href
                }
            };

            if (isMobileDevice()) {
                defaultOptions.vibrate = [100, 50, 100, 50, 100];
                defaultOptions.requireInteraction = false; // Don't require interaction on mobile
                defaultOptions.actions = [{
                    action: 'view',
                    title: 'View'
                }, {
                    action: 'close',
                    title: 'Close'
                }];
                // Ensure content is short and clear for mobile
                if (!options.body || options.body.length > 100) {
                    options.body = options.body ? options.body.substring(0, 100) : 'Tap to view more';
                }
            }

            const finalOptions = { ...defaultOptions, ...options };
            console.log('Sending notification with options:', finalOptions);
            
            try {
                // Use service worker to show notification
                await swRegistration.showNotification(title, finalOptions);
                console.log('Notification sent successfully via Service Worker');
            } catch (error) {
                console.error('Error sending notification via Service Worker:', error);
                
                // Fallback to regular notification if service worker fails
                try {
                    const notification = new Notification(title, finalOptions);
                    console.log('Fallback notification sent successfully');
                    
                    notification.onclick = function(event) {
                        console.log('Notification clicked');
                        event.preventDefault();
                        window.focus();
                        if (options.onClick) {
                            options.onClick();
                        }
                        notification.close();
                    };

                    if (isMobileDevice()) {
                        // Auto-close after shorter time on mobile
                        setTimeout(() => notification.close(), 3000);
                    }
                } catch (fallbackError) {
                    console.error('Fallback notification failed:', fallbackError);
                    alert('Unable to send notifications. Please check your browser settings.');
                }
            }
        } else {
            console.log('Requesting permission as it was not granted...');
            await requestNotificationPermission();
        }
    } catch (error) {
        console.error('Error in notification process:', error);
        if (isMobileDevice()) {
            // Show a visible alert instead on mobile if all notification attempts fail
            alert(`Productivity reminder: ${title} - ${options.body || 'Keep going!'}`);
        }
    }
}

// Function to start the productivity reminder
function startProductivityReminder() {
    console.log('Starting productivity reminder...');
    
    // Send first notification with slight delay to ensure service worker is ready
    setTimeout(() => sendProductivityReminder(), 2000);
    
    console.log('Setting up interval for notifications...');
    const thirtyMinutes = 30 * 60 * 1000; // 30 minutes in milliseconds
    const intervalId = setInterval(sendProductivityReminder, thirtyMinutes);
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
    const messages = [
        'Hope you are doing something productive!',
        'Keep focusing on your goals!',
        'A little progress each day adds up!',
        'Stay productive and focused!'
    ];
    const randomMessage = messages[Math.floor(Math.random() * messages.length)];
    
    sendNotification('Productivity Reminder', {
        body: `Abel, ${randomMessage}`,
        requireInteraction: !isMobileDevice(), // Only require interaction on desktop
        timestamp: Date.now(),
        tag: 'productivity-reminder'
    });
}

// Initialize when the script loads
console.log('Setting up notification system...');
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, requesting notification permission...');
    // Add a visible button for mobile to trigger notifications (helps with iOS)
    if (isMobileDevice()) {
        const notificationBtn = document.createElement('button');
        notificationBtn.innerText = 'Enable Notifications';
        notificationBtn.style.position = 'fixed';
        notificationBtn.style.bottom = '20px';
        notificationBtn.style.right = '20px';
        notificationBtn.style.zIndex = '9999';
        notificationBtn.style.padding = '10px';
        notificationBtn.style.backgroundColor = '#007bff';
        notificationBtn.style.color = 'white';
        notificationBtn.style.border = 'none';
        notificationBtn.style.borderRadius = '5px';
        notificationBtn.onclick = requestNotificationPermission;
        document.body.appendChild(notificationBtn);
    }
    
    setTimeout(requestNotificationPermission, 1000);
});
