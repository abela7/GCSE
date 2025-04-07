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
function log(message) {
    console.log(`[ServiceWorker] ${message}`);
}

// Get the base URL
const getBaseUrl = () => {
    return self.registration.scope;
};

const CACHE_NAME = 'gcse-app-v1';
const OFFLINE_URL = '/GCSE/offline.html';

// Assets to cache
const ASSETS_TO_CACHE = [
    '/GCSE/',
    '/GCSE/offline.html',
    '/GCSE/assets/css/style.css',
    '/GCSE/assets/js/mobile-notifications.js',
    '/GCSE/manifest.json',
    '/GCSE/assets/images/icon-192x192.png',
    '/GCSE/assets/images/icon-96x96.png'
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
    log('Installing...');
    self.skipWaiting();
    log('Installed');
});

// Service Worker activation
self.addEventListener('activate', event => {
    log('Activating...');
    event.waitUntil(
        Promise.all([
            clients.claim(),
            checkScheduledNotifications() // Start the notification checker
        ]).then(() => {
            log('Activated and claimed clients');
            // Notify all clients that the service worker is ready
            return self.clients.matchAll()
                .then(clients => {
                    clients.forEach(client => client.postMessage({ type: 'SW_READY' }));
                });
        })
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    log(`Notification clicked: ${event.notification.tag}`);
    
    // Close the notification
    event.notification.close();

    // Handle different notification types
    let url = '/GCSE/';
    switch (event.notification.tag) {
        case 'hourly-reminder':
            url = '/GCSE/tasks.php';
            break;
        case 'morning-motivation':
            url = '/GCSE/dashboard.php';
            break;
        case 'night-reminder':
            url = '/GCSE/bible-study.php';
            break;
    }

    // Focus or open the appropriate page
    event.waitUntil(
        clients.matchAll({ type: 'window' })
            .then(clientList => {
                // Try to focus an existing window
                for (const client of clientList) {
                    if (client.url.includes(url) && 'focus' in client) {
                        return client.focus();
                    }
                }
                // If no existing window, open a new one
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// Handle push messages (for future use with push notifications)
self.addEventListener('push', event => {
    log('Push message received');
    
    if (!event.data) {
        log('No data in push message');
        return;
    }

    try {
        const data = event.data.json();
        log(`Push data: ${JSON.stringify(data)}`);

        event.waitUntil(
            self.registration.showNotification(data.title, {
                body: data.body,
                icon: data.icon || '/assets/images/icon-192x192.png',
                tag: data.tag,
                vibrate: [200, 100, 200],
                data: data
            })
        );
    } catch (error) {
        log(`Error handling push message: ${error}`);
    }
});

// Handle periodic sync (for future use with background sync)
self.addEventListener('periodicsync', event => {
    if (event.tag === 'check-notifications') {
        event.waitUntil(checkScheduledNotifications());
    }
});

// Check for scheduled notifications
async function checkScheduledNotifications() {
    const now = new Date();
    const hour = now.getHours();
    const minute = now.getMinutes();
    const second = now.getSeconds();

    try {
        // Get notification states from clients
        const clients = await self.clients.matchAll();
        if (clients.length === 0) {
            log('No active clients found');
            return;
        }

        // Calculate next check time to ensure we don't miss notifications
        const msUntilNextMinute = (60 - second) * 1000;
        setTimeout(checkScheduledNotifications, msUntilNextMinute);

        // Ask the first client for notification states
        const client = clients[0];
        client.postMessage({ type: 'GET_NOTIFICATION_STATES' });
    } catch (error) {
        log(`Error checking scheduled notifications: ${error}`);
        // Retry in 1 minute if there's an error
        setTimeout(checkScheduledNotifications, 60000);
    }
}

// Listen for messages from the page
self.addEventListener('message', event => {
    log(`Message received: ${JSON.stringify(event.data)}`);

    if (event.data.type === 'NOTIFICATION_STATES') {
        handleNotificationStates(event.data.states, event.data.isTest);
    }
});

// Handle notification states received from the page
function handleNotificationStates(states, isTest = false) {
    if (!states) return;

    const now = new Date();
    const hour = now.getHours();
    const minute = now.getMinutes();

    log(`Checking notifications at ${hour}:${minute}${isTest ? ' (TEST MODE)' : ''}`);

    // For testing, we ignore the time restrictions
    if (isTest) {
        if (states.hourly) {
            log(`Testing hourly notification`);
            showScheduledNotification('hourly', hour);
        }
        if (states.morning) {
            log(`Testing morning notification`);
            showScheduledNotification('morning');
        }
        if (states.night) {
            log(`Testing night notification`);
            showScheduledNotification('night');
        }
        return;
    }

    // Regular time-based checks
    if (minute === 0 && hour >= 21 && states.hourly) {
        log(`Triggering hourly notification at ${hour}:00`);
        showScheduledNotification('hourly', hour);
    }

    if (hour === 7 && minute === 0 && states.morning) {
        log(`Triggering morning notification`);
        showScheduledNotification('morning');
    }

    if (hour === 0 && minute === 0 && states.night) {
        log(`Triggering night notification`);
        showScheduledNotification('night');
    }
}

// Show a scheduled notification
async function showScheduledNotification(type, hour = null) {
    const notifications = {
        hourly: {
            title: "Time Check",
            body: hour ? `${hour}:00 - Stay focused! Keep working on your tasks.` : "Stay focused on your tasks!",
            tag: "hourly-reminder",
            requireInteraction: true
        },
        morning: {
            title: "Good Morning Abela",
            body: "Make sure you stay productive today, Time is your only Precious Gift!",
            tag: "morning-motivation",
            requireInteraction: true
        },
        night: {
            title: "Good Night Reflection",
            body: "Tomorrow is a new Day! Thank GOD for everything, Do not forget to Study Bible.",
            tag: "night-reminder",
            requireInteraction: true
        }
    };

    const config = notifications[type];
    if (!config) return;

    try {
        await self.registration.showNotification(config.title, {
            body: config.body,
            icon: '/assets/images/icon-192x192.png',
            tag: config.tag,
            vibrate: [200, 100, 200],
            requireInteraction: true,
            silent: false,
            timestamp: Date.now()
        });
        log(`Scheduled notification shown: ${type} at ${new Date().toLocaleTimeString()}`);
    } catch (error) {
        log(`Error showing scheduled notification: ${error}`);
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