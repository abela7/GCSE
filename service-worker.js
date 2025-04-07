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

const CACHE_NAME = 'do-it-v1';
const OFFLINE_URL = '/offline.html';

// Assets to cache
const ASSETS_TO_CACHE = [
    '/',
    '/index.php',
    '/offline.html',
    '/assets/css/style.css',
    '/assets/js/task-notifications.js',
    '/manifest.json',
    '/assets/images/icon-72x72.png',
    '/assets/images/icon-96x96.png',
    '/assets/images/icon-128x128.png',
    '/assets/images/icon-144x144.png',
    '/assets/images/icon-152x152.png',
    '/assets/images/icon-192x192.png',
    '/assets/images/icon-384x384.png',
    '/assets/images/icon-512x512.png'
];

// Install event - cache assets
self.addEventListener('install', event => {
    console.log('[Service Worker] Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[Service Worker] Caching app shell');
                return cache.addAll(ASSETS_TO_CACHE);
            })
            .then(() => {
                console.log('[Service Worker] Install completed');
                return self.skipWaiting();
            })
    );
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
    console.log('[Service Worker] Activating...');
    event.waitUntil(
        Promise.all([
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(name => name !== CACHE_NAME)
                        .map(name => {
                            console.log('[Service Worker] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            }),
            self.clients.claim()
        ])
    );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', event => {
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    return caches.match(OFFLINE_URL);
                })
        );
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    return response;
                }
                return fetch(event.request);
            })
            .catch(() => {
                if (event.request.mode === 'navigate') {
                    return caches.match(OFFLINE_URL);
                }
            })
    );
});

// Push event - handle incoming push messages
self.addEventListener('push', event => {
    console.log('[Service Worker] Push received');
    let notification = {
        title: 'Do-It',
        body: 'New notification',
        icon: '/assets/images/icon-192x192.png',
        badge: '/assets/images/icon-96x96.png',
        vibrate: [200, 100, 200],
        tag: 'do-it-notification',
        renotify: true,
        actions: [
            {
                action: 'open',
                title: 'Open App'
            },
            {
                action: 'close',
                title: 'Close'
            }
        ]
    };

    try {
        if (event.data) {
            const data = event.data.json();
            notification = {
                ...notification,
                ...data
            };
        }
    } catch (e) {
        console.error('[Service Worker] Error parsing push data:', e);
    }

    event.waitUntil(
        self.registration.showNotification(notification.title, notification)
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    console.log('[Service Worker] Notification click received');

    event.notification.close();

    let urlToOpen = '/';
    if (event.action === 'open' || !event.action) {
        switch (event.notification.tag) {
            case 'task-reminder':
                urlToOpen = '/pages/tasks/index.php';
                break;
            case 'exam-reminder':
                urlToOpen = '/pages/exam_countdown.php';
                break;
            default:
                urlToOpen = '/';
        }
    }

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        })
        .then(function(clientList) {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            return clients.openWindow(urlToOpen);
        })
    );
});

// Background sync event
self.addEventListener('sync', event => {
    console.log('[Service Worker] Background sync:', event.tag);
    if (event.tag === 'sync-notifications') {
        event.waitUntil(syncNotifications());
    }
});

// Periodic background sync event
self.addEventListener('periodicsync', event => {
    console.log('[Service Worker] Periodic background sync:', event.tag);
    if (event.tag === 'sync-notifications') {
        event.waitUntil(syncNotifications());
    }
});

// Function to sync notifications
async function syncNotifications() {
    console.log('[Service Worker] Syncing notifications...');
    try {
        // Check task reminders
        const taskResponse = await fetch('/api/get_incomplete_tasks.php');
        const taskData = await taskResponse.json();
        if (taskData.success && taskData.tasks.length > 0) {
            await self.registration.showNotification('Task Reminder', {
                body: `You have ${taskData.tasks.length} incomplete tasks`,
                icon: '/assets/images/icon-192x192.png',
                badge: '/assets/images/icon-96x96.png',
                vibrate: [200, 100, 200],
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
                ]
            });
        }

        // Check exam reminders
        const examResponse = await fetch('/api/get_exam_countdown.php');
        const examData = await examResponse.json();
        if (examData.success && examData.exams.length > 0) {
            await self.registration.showNotification('Exam Reminder', {
                body: `You have upcoming exams`,
                icon: '/assets/images/icon-192x192.png',
                badge: '/assets/images/icon-96x96.png',
                vibrate: [200, 100, 200],
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
                ]
            });
        }

        // Send motivation
        const motivationResponse = await fetch('/api/get_motivational_message.php');
        const motivationData = await motivationResponse.json();
        if (motivationData.success) {
            await self.registration.showNotification('Daily Motivation', {
                body: motivationData.message,
                icon: '/assets/images/icon-192x192.png',
                badge: '/assets/images/icon-96x96.png',
                vibrate: [200, 100, 200],
                tag: 'motivation',
                renotify: true,
                actions: [
                    {
                        action: 'close',
                        title: 'Thanks!'
                    }
                ]
            });
        }
    } catch (error) {
        console.error('[Service Worker] Error syncing notifications:', error);
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