// Service Worker for handling notifications
self.addEventListener('install', (event) => {
    console.log('Service Worker installed');
});

self.addEventListener('activate', (event) => {
    console.log('Service Worker activated');
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
    console.log('Notification clicked:', event.notification.tag);
    event.notification.close();
    
    // Focus on the main window or open a new one
    event.waitUntil(
        clients.matchAll({type: 'window'}).then(windowClients => {
            if (windowClients.length > 0) {
                windowClients[0].focus();
            } else {
                clients.openWindow('/');
            }
        })
    );
});

// Handle push notifications
self.addEventListener('push', (event) => {
    console.log('Push notification received');
    
    const options = {
        body: event.data.text(),
        icon: '/assets/favicon/favicon-32x32.png',
        badge: '/assets/favicon/favicon-32x32.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'close',
                title: 'Close',
                icon: '/assets/favicon/favicon-16x16.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('Productivity Reminder', options)
    );
}); 