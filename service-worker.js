// Enhanced debug logging function
function log(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const prefix = `[ServiceWorker ${type.toUpperCase()}]`;
    console.log(`${prefix} [${timestamp}] ${message}`);
}

// Debug function to log all details about a request
function debugRequest(prefix, request) {
    log(`${prefix} Details:`, 'debug');
    log(JSON.stringify({
        url: request.url,
        method: request.method,
        mode: request.mode,
        credentials: request.credentials,
        destination: request.destination,
        referrer: request.referrer,
        headers: Array.from(request.headers.entries()),
        cache: request.cache
    }, null, 2), 'debug');
}

// Debug function to log response details
function debugResponse(prefix, response) {
    if (!response) {
        log(`${prefix}: Response is null or undefined`, 'error');
        return;
    }
    log(`${prefix} Details:`, 'debug');
    log(JSON.stringify({
        url: response.url,
        status: response.status,
        statusText: response.statusText,
        type: response.type,
        headers: Array.from(response.headers.entries()),
        redirected: response.redirected
    }, null, 2), 'debug');
}

// Get the base URL
const getBaseUrl = () => {
    return self.registration.scope;
};

const CACHE_NAME = 'gcse-app-v1';
const OFFLINE_URL = '/offline.html';

// Assets to cache
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

// Service Worker installation
self.addEventListener('install', event => {
    log('Installation started', 'info');
    event.waitUntil(
        Promise.resolve()
            .then(() => {
                log('Skipping waiting', 'info');
                return self.skipWaiting();
            })
            .then(() => {
                log('Installation completed successfully', 'success');
            })
            .catch(error => {
                log(`Installation failed: ${error}`, 'error');
                throw error;
            })
    );
});

// Service Worker activation
self.addEventListener('activate', event => {
    log('Activation started', 'info');
    event.waitUntil(
        Promise.all([
            clients.claim(),
            // Initialize periodic sync if available
            (async () => {
                try {
                    if ('periodicSync' in registration) {
                        await registration.periodicSync.register('check-notifications', {
                            minInterval: 60000 // Check every minute
                        });
                        log('Periodic sync registered', 'success');
                    }
                } catch (error) {
                    log(`Periodic sync registration failed: ${error}`, 'warning');
                }
            })()
        ])
        .then(() => {
            log('Activation completed successfully', 'success');
            return self.clients.matchAll();
        })
        .then(clients => {
            clients.forEach(client => {
                log(`Notifying client: ${client.id}`, 'info');
                client.postMessage({ type: 'SW_ACTIVATED' });
            });
        })
        .catch(error => {
            log(`Activation failed: ${error}`, 'error');
            throw error;
        })
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    log(`Notification clicked: ${event.notification.tag}`, 'info');
    
    event.notification.close();
    
    let url = '/';
    const data = event.notification.data || {};
    
    switch (event.notification.tag) {
        case 'hourly-reminder':
            url = '/tasks.php';
            break;
        case 'morning-motivation':
            url = '/dashboard.php';
            break;
        case 'night-reminder':
            url = '/bible-study.php';
            break;
    }
    
    event.waitUntil(
        clients.matchAll({ type: 'window' })
            .then(clientList => {
                for (const client of clientList) {
                    if (client.url.includes(url) && 'focus' in client) {
                        return client.focus();
                    }
                }
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// Handle push messages
self.addEventListener('push', event => {
    log('Push message received', 'info');
    
    if (event.data) {
        try {
            const data = event.data.json();
            event.waitUntil(
                self.registration.showNotification(data.title, {
                    body: data.body,
                    icon: data.icon || '../assets/images/icon-192x192.png',
                    tag: data.tag,
                    vibrate: [200, 100, 200],
                    requireInteraction: true,
                    data: data
                })
            );
        } catch (error) {
            log(`Error handling push message: ${error}`, 'error');
        }
    }
});

// Handle periodic sync events
self.addEventListener('periodicsync', event => {
    if (event.tag === 'check-notifications') {
        event.waitUntil(checkScheduledNotifications());
    }
});

// Function to check scheduled notifications
async function checkScheduledNotifications() {
    const now = new Date();
    const hour = now.getHours();
    const minute = now.getMinutes();
    
    log(`Checking scheduled notifications at ${hour}:${minute}`, 'info');
    
    try {
        const clients = await self.clients.matchAll();
        if (clients.length === 0) {
            log('No active clients found', 'warning');
            return;
        }
        
        // Send check request to all clients
        clients.forEach(client => {
            client.postMessage({
                type: 'CHECK_NOTIFICATIONS',
                time: { hour, minute }
            });
        });
        
        log('Notification check completed', 'success');
    } catch (error) {
        log(`Error checking notifications: ${error}`, 'error');
    }
}

// Fetch event - serve from cache or network
self.addEventListener('fetch', event => {
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    log('Navigation fetch failed, serving offline page');
                    return caches.match(OFFLINE_URL);
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
                    return caches.match(OFFLINE_URL);
                }
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