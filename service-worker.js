// Debug function to log all details about a request
function debugRequest(prefix, request) {
    console.log(`[DEBUG] ${prefix} Details:`, {
        url: request.url,
        method: request.method,
        mode: request.mode,
        credentials: request.credentials,
        destination: request.destination,
        referrer: request.referrer,
        headers: Array.from(request.headers.entries()),
        cache: request.cache
    });
}

// Debug function to log response details
function debugResponse(prefix, response) {
    if (!response) {
        console.log(`[DEBUG] ${prefix}: Response is null or undefined`);
        return;
    }
    console.log(`[DEBUG] ${prefix} Details:`, {
        url: response.url,
        status: response.status,
        statusText: response.statusText,
        type: response.type,
        headers: Array.from(response.headers.entries()),
        redirected: response.redirected
    });
}

const CACHE_NAME = 'web-app-v1';
const OFFLINE_URL = '/offline.html';

// Assets to cache
const ASSETS_TO_CACHE = [
    '/',
    '/offline.html',
    '/assets/css/style.css',
    '/assets/js/task-notifications.js',
    '/assets/images/icon-192x192.png',
    '/manifest.json'
];

// Install event - cache assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(ASSETS_TO_CACHE))
    );
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames
                    .filter(name => name !== CACHE_NAME)
                    .map(name => caches.delete(name))
            );
        })
    );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                return response || fetch(event.request).catch(() => {
                    if (event.request.mode === 'navigate') {
                        return caches.match(OFFLINE_URL);
                    }
                });
            })
    );
});

// Push event - handle incoming push messages
self.addEventListener('push', event => {
    const options = {
        body: event.data.text(),
        icon: '/assets/images/icon-192x192.png',
        badge: '/assets/images/icon-192x192.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'View Details'
            },
            {
                action: 'close',
                title: 'Close'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('Web App Notification', options)
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Background sync event
self.addEventListener('sync', event => {
    if (event.tag === 'sync-notifications') {
        event.waitUntil(syncNotifications());
    }
});

// Function to sync notifications
async function syncNotifications() {
    try {
        // Check task reminders
        const taskResponse = await fetch('/api/get_incomplete_tasks.php');
        const taskData = await taskResponse.json();
        if (taskData.success && taskData.tasks.length > 0) {
            self.registration.showNotification('Task Reminder', {
                body: `You have ${taskData.tasks.length} incomplete tasks`,
                icon: '/assets/images/icon-192x192.png',
                vibrate: [100, 50, 100]
            });
        }

        // Check exam reminders
        const examResponse = await fetch('/api/get_exam_countdown.php');
        const examData = await examResponse.json();
        if (examData.success && examData.exams.length > 0) {
            self.registration.showNotification('Exam Reminder', {
                body: `You have upcoming exams`,
                icon: '/assets/images/icon-192x192.png',
                vibrate: [100, 50, 100]
            });
        }

        // Send motivation
        const motivationResponse = await fetch('/api/get_motivational_message.php');
        const motivationData = await motivationResponse.json();
        if (motivationData.success) {
            self.registration.showNotification('Daily Motivation', {
                body: motivationData.message,
                icon: '/assets/images/icon-192x192.png',
                vibrate: [100, 50, 100]
            });
        }
    } catch (error) {
        console.error('Error syncing notifications:', error);
    }
}

// Handle root URL redirect
function handleRootRequest(request) {
    // If it's the root URL, redirect to dashboard
    const url = new URL(request.url);
    if (url.pathname === '/' || url.pathname === '/index.php') {
        return Response.redirect(`${url.origin}/pages/dashboard.php`, 302);
    }
    return null;
}

// Fetch Event
self.addEventListener('fetch', (event) => {
    // Check for root URL redirect
    const redirectResponse = handleRootRequest(event.request);
    if (redirectResponse) {
        event.respondWith(redirectResponse);
        return;
    }

    // Handle navigation requests (HTML pages)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    return response;
                })
                .catch(error => {
                    return caches.match('offline.html');
                })
        );
        return;
    }

    // Handle all other requests
    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }

                return fetch(event.request)
                    .then(response => {
                        if (!response || response.status !== 200) {
                            return response;
                        }

                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    })
                    .catch(error => {
                        if (event.request.mode === 'navigate') {
                            return caches.match('offline.html');
                        }
                        throw error;
                    });
            })
    );
}); 