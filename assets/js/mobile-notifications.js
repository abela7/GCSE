// Mobile Notifications Handler
class MobileNotifications {
    constructor() {
        this.swRegistration = null;
        this.isAndroid = /Android/i.test(navigator.userAgent);
        this.initialized = false;
        // Get the base URL from the current page
        this.baseUrl = window.location.origin;
        console.log('[Notifications] Base URL:', this.baseUrl);
    }

    async init() {
        console.log('[Notifications] Initializing...');
        console.log('[Notifications] User Agent:', navigator.userAgent);
        console.log('[Notifications] Is Android:', this.isAndroid);
        
        if (!('serviceWorker' in navigator)) {
            console.error('[Notifications] Service Worker not supported');
            return false;
        }
        
        if (!('Notification' in window)) {
            console.error('[Notifications] Notifications not supported');
            return false;
        }

        try {
            // First, unregister any existing service workers
            const existingRegs = await navigator.serviceWorker.getRegistrations();
            for (let reg of existingRegs) {
                console.log('[Notifications] Unregistering existing SW:', reg.scope);
                await reg.unregister();
            }

            // Register new service worker
            console.log('[Notifications] Registering new service worker...');
            this.swRegistration = await navigator.serviceWorker.register('/service-worker.js');
            console.log('[Notifications] Service Worker registered');

            // Wait for the service worker to be ready
            console.log('[Notifications] Waiting for SW to be ready...');
            await navigator.serviceWorker.ready;
            console.log('[Notifications] Service Worker is ready');

            // Force activation if needed
            if (this.swRegistration.active) {
                console.log('[Notifications] SW is already active');
            } else if (this.swRegistration.waiting) {
                console.log('[Notifications] SW is waiting, activating...');
                this.swRegistration.waiting.postMessage({type: 'SKIP_WAITING'});
            } else if (this.swRegistration.installing) {
                console.log('[Notifications] SW is installing, waiting...');
                this.swRegistration.installing.addEventListener('statechange', (event) => {
                    if (event.target.state === 'installed') {
                        console.log('[Notifications] SW installed, activating...');
                        event.target.postMessage({type: 'SKIP_WAITING'});
                    }
                });
            }

            // Add message listener for reload after activation
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data.type === 'RELOAD_PAGE') {
                    window.location.reload();
                }
            });

            // Check permission
            const permission = await this.checkPermission();
            console.log('[Notifications] Current permission:', permission);
            
            this.initialized = true;
            return true;
        } catch (error) {
            console.error('[Notifications] Initialization failed:', error);
            return false;
        }
    }

    async checkPermission() {
        if (!('Notification' in window)) {
            console.error('[Notifications] Notifications API not available');
            return 'unsupported';
        }
        const permission = Notification.permission;
        console.log('[Notifications] Current permission status:', permission);
        return permission;
    }

    async requestPermission() {
        try {
            console.log('[Notifications] Requesting permission...');
            const permission = await Notification.requestPermission();
            console.log('[Notifications] Permission response:', permission);
            
            if (permission === 'granted') {
                // Re-initialize to ensure service worker is ready
                await this.init();
            }
            
            return permission;
        } catch (error) {
            console.error('[Notifications] Permission request failed:', error);
            return 'denied';
        }
    }

    async ensureServiceWorkerReady() {
        console.log('[Notifications] Ensuring SW is ready...');
        
        if (!this.initialized || !this.swRegistration) {
            console.log('[Notifications] Not initialized, reinitializing...');
            await this.init();
        }

        try {
            // Double check service worker is ready
            const registration = await navigator.serviceWorker.ready;
            console.log('[Notifications] Got ready registration:', registration);
            
            if (registration !== this.swRegistration) {
                console.log('[Notifications] Updating registration reference');
                this.swRegistration = registration;
            }
            return this.swRegistration;
        } catch (error) {
            console.error('[Notifications] Error ensuring SW ready:', error);
            return null;
        }
    }

    async showNotification(title, options = {}) {
        console.log('[Notifications] Showing notification:', title);
        
        try {
            const registration = await this.ensureServiceWorkerReady();
            if (!registration) {
                console.error('[Notifications] No active registration');
                return false;
            }

            const permission = await this.checkPermission();
            if (permission !== 'granted') {
                console.error('[Notifications] Permission not granted');
                return false;
            }

            // Default options with absolute paths
            const defaultOptions = {
                icon: `${this.baseUrl}/assets/images/icon-192x192.png`,
                badge: `${this.baseUrl}/assets/images/icon-96x96.png`,
                vibrate: [200, 100, 200],
                requireInteraction: false,
                renotify: true,
                silent: false
            };

            // Merge options and ensure paths are absolute
            const notificationOptions = { ...defaultOptions, ...options };
            if (options.data && options.data.url) {
                notificationOptions.data.url = `${this.baseUrl}${options.data.url}`;
            }

            console.log('[Notifications] Showing with options:', notificationOptions);

            // Use the active service worker to show notification
            await registration.showNotification(title, notificationOptions);
            console.log('[Notifications] Notification shown successfully');
            return true;
        } catch (error) {
            console.error('[Notifications] Show notification failed:', error);
            return false;
        }
    }

    // Test notifications
    async testTaskNotification() {
        console.log('[Notifications] Testing task notification...');
        return this.showNotification('Task Reminder', {
            body: 'This is a test task notification',
            tag: 'task-reminder',
            data: {
                type: 'task',
                url: '/pages/tasks/index.php'
            },
            actions: [
                {
                    action: 'view',
                    title: 'View Tasks'
                },
                {
                    action: 'close',
                    title: 'Close'
                }
            ]
        });
    }

    async testExamNotification() {
        console.log('[Notifications] Testing exam notification...');
        return this.showNotification('Exam Reminder', {
            body: 'This is a test exam notification',
            tag: 'exam-reminder',
            data: {
                type: 'exam',
                url: '/pages/exam_countdown.php'
            },
            actions: [
                {
                    action: 'view',
                    title: 'View Exams'
                },
                {
                    action: 'close',
                    title: 'Close'
                }
            ]
        });
    }

    async testMotivationalNotification() {
        console.log('[Notifications] Testing motivational notification...');
        return this.showNotification('Daily Motivation', {
            body: 'You can do it! Keep pushing forward! 🌟',
            tag: 'motivation',
            data: {
                type: 'motivation'
            }
        });
    }
}

// Initialize notifications
const notifications = new MobileNotifications();

// Export for use in other files
window.notifications = notifications; 