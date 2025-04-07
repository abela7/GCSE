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

// Debug logging function
const log = (message, ...args) => {
    console.log(`[Service Worker] ${message}`, ...args);
};

// Get the base URL
const getBaseUrl = () => {
    return self.registration.scope;
};

const CACHE_NAME = 'web-app-v1';
const OFFLINE_URL = '/offline.html';

// Assets to cache - paths will be made absolute during install
const ASSETS_TO_CACHE = [
    '/',
    '/offline.html',
    '/assets/css/style.css',
    '/assets/js/mobile-notifications.js',
    '/manifest.json',
    '/assets/images/icon-192x192.png',
    '/assets/images/icon-96x96.png'
];

// Make a URL absolute using the service worker scope
const makeAbsoluteUrl = (path) => {
    const base = getBaseUrl();
    // Remove leading slash if present as base already has trailing slash
    const cleanPath = path.startsWith('/') ? path.slice(1) : path;
    return new URL(cleanPath, base).href;
};

// Install event - cache assets
self.addEventListener('install', event => {
    log('Installing...');
    log('Scope:', self.registration.scope);
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                log('Caching app shell');
                // Make all paths absolute before caching
                const absoluteUrls = ASSETS_TO_CACHE.map(path => makeAbsoluteUrl(path));
                log('Caching URLs:', absoluteUrls);
                return cache.addAll(absoluteUrls);
            })
            .then(() => {
                log('Install completed');
                return self.skipWaiting();
            })
            .catch(error => {
                log('Install failed:', error);
                throw error;
            })
    );
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
    log('Activating...');
    event.waitUntil(
        Promise.all([
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(name => name !== CACHE_NAME)
                        .map(name => {
                            log('Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            }),
            self.clients.claim().then(() => {
                log('Claimed clients');
            })
        ])
    );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', event => {
    log('Fetch:', event.request.url);
    
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    log('Navigation fetch failed, serving offline page');
                    return caches.match(makeAbsoluteUrl(OFFLINE_URL));
                })
        );
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    log('Serving from cache:', event.request.url);
                    return response;
                }
                log('Fetching from network:', event.request.url);
                return fetch(event.request);
            })
            .catch(() => {
                if (event.request.mode === 'navigate') {
                    log('Fetch failed, serving offline page');
                    return caches.match(makeAbsoluteUrl(OFFLINE_URL));
                }
            })
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    log('Notification click:', event);

    // Close the notification
    event.notification.close();

    // Get the notification data and base URL
    const data = event.notification.data || {};
    const baseUrl = getBaseUrl();
    let urlToOpen = baseUrl;

    // Handle different notification types
    if (data.url) {
        urlToOpen = new URL(data.url, baseUrl).href;
    } else {
        switch (event.notification.tag) {
            case 'task-reminder':
                urlToOpen = new URL('/pages/tasks/index.php', baseUrl).href;
                break;
            case 'exam-reminder':
                urlToOpen = new URL('/pages/exam_countdown.php', baseUrl).href;
                break;
        }
    }

    log('Opening URL:', urlToOpen);

    // Handle action buttons
    if (event.action === 'view') {
        urlToOpen = data.url ? new URL(data.url, baseUrl).href : urlToOpen;
    }

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        })
        .then(function(clientList) {
            log('Found clients:', clientList.length);
            
            // If we have a client, focus it
            for (let client of clientList) {
                if (client.url === urlToOpen && 'focus' in client) {
                    log('Focusing existing client');
                    return client.focus();
                }
            }
            // If no client is found, open a new window
            log('Opening new window');
            return clients.openWindow(urlToOpen);
        })
    );
});

// Push event - handle incoming push messages
self.addEventListener('push', event => {
    log('Push received:', event);

    let notification = {
        title: 'Web-App',
        body: 'New notification',
        icon: makeAbsoluteUrl('/assets/images/icon-192x192.png'),
        badge: makeAbsoluteUrl('/assets/images/icon-96x96.png'),
        vibrate: [200, 100, 200],
        tag: 'default',
        renotify: true,
        requireInteraction: false
    };

    if (event.data) {
        try {
            const data = event.data.json();
            notification = {
                ...notification,
                ...data
            };
        } catch (e) {
            log('Error parsing push data:', e);
        }
    }

    event.waitUntil(
        self.registration.showNotification(notification.title, notification)
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

// Add to notification-settings.php
async function checkServiceWorker() {
    if ('serviceWorker' in navigator) {
        const registration = await navigator.serviceWorker.getRegistration();
        console.log('Current SW:', registration);
        if (registration) {
            console.log('SW State:', registration.active ? 'active' : 'inactive');
            console.log('SW Scope:', registration.scope);
        }
    }
}

async function requestNotificationPermission() {
    if (!("Notification" in window)) {
        console.error("Browser doesn't support notifications");
        updateNotificationUI('unsupported');
        return false;
    }

    try {
        const permission = await Notification.requestPermission();
        updateNotificationUI(permission);
        
        if (permission === "granted") {
            const registration = await registerServiceWorker();
            if (!registration) {
                throw new Error('Failed to register service worker');
            }
            return true;
        }
        return false;
    } catch (error) {
        console.error('Notification permission error:', error);
        updateNotificationUI('error');
        return false;
    }
} 