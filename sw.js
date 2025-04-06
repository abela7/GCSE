const CACHE_NAME = 'do-it-app-v1';
const OFFLINE_URL = '/offline.html';
const NOTIFICATION_INTERVAL = 30 * 60 * 1000; // 30 minutes in milliseconds
const urlsToCache = [
    '/',
    '/index.php',
    '/manifest.json',
    '/pages/Today.php',
    '/pages/EnglishPractice/review.php',
    '/includes/header.php',
    '/includes/footer.php',
    '/assets/css/style.css',
    '/assets/css/main.css',
    '/assets/css/responsive.css',
    '/assets/js/main.js',
    '/favicon/favicon.ico',
    '/favicon/favicon-16x16.png',
    '/favicon/favicon-32x32.png',
    '/favicon/android-chrome-192x192.png',
    '/favicon/android-chrome-512x512.png',
    '/favicon/apple-touch-icon.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'
];

// Install event - cache files
self.addEventListener('install', event => {
    console.log('[ServiceWorker] Install');
    event.waitUntil(
        Promise.all([
            caches.open(CACHE_NAME)
                .then(cache => {
                    console.log('[ServiceWorker] Caching app shell');
                    return cache.addAll(urlsToCache);
                })
                .catch(error => {
                    console.error('[ServiceWorker] Cache install error:', error);
                }),
            // Set up periodic notifications
            setupPeriodicNotifications()
        ])
    );
    // Force the waiting service worker to become the active service worker
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('[ServiceWorker] Activate');
    event.waitUntil(
        Promise.all([
            // Take control of all pages under this service worker's scope
            self.clients.claim(),
            // Remove old caches
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('[ServiceWorker] Removing old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
        ])
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    console.log('[ServiceWorker] Return from cache:', event.request.url);
                    return response;
                }

                return fetch(event.request)
                    .then(response => {
                        // Check if we received a valid response
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Clone the response as it can only be consumed once
                        const responseToCache = response.clone();

                        caches.open(CACHE_NAME)
                            .then(cache => {
                                console.log('[ServiceWorker] Caching new resource:', event.request.url);
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    })
                    .catch(error => {
                        console.error('[ServiceWorker] Fetch failed:', error);
                        // Return the offline page for navigation requests
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_URL);
                        }
                        return null;
                    });
            })
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    // Navigate to the app when notification is clicked
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(clientList => {
            for (const client of clientList) {
                if (client.url && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow('/');
            }
        })
    );
});

// Function to show notification
async function showNotification() {
    try {
        const registration = await self.registration;
        const title = "Do It! - Daily Reminder";
        const options = {
            body: "Don't forget to check your tasks and habits for today!",
            icon: '/favicon/android-chrome-192x192.png',
            badge: '/favicon/favicon-32x32.png',
            vibrate: [200, 100, 200],
            tag: 'do-it-reminder',
            renotify: true,
            actions: [
                {
                    action: 'open',
                    title: 'Open App'
                },
                {
                    action: 'dismiss',
                    title: 'Dismiss'
                }
            ]
        };
        
        await registration.showNotification(title, options);
    } catch (error) {
        console.error('Error showing notification:', error);
    }
}

// Function to set up periodic notifications
async function setupPeriodicNotifications() {
    try {
        // First, check if we have permission
        if (Notification.permission !== 'granted') {
            return;
        }
        
        // Show first notification immediately
        await showNotification();
        
        // Set up interval for future notifications
        setInterval(showNotification, NOTIFICATION_INTERVAL);
    } catch (error) {
        console.error('Error setting up notifications:', error);
    }
} 