// Mobile Notifications Handler
class MobileNotifications {
    constructor() {
        this.swRegistration = null;
        this.isAndroid = /Android/i.test(navigator.userAgent);
        this.initialized = false;
    }

    async init() {
        console.log('[Notifications] Initializing...');
        
        if (!('serviceWorker' in navigator) || !('Notification' in window)) {
            console.log('[Notifications] Browser does not support notifications');
            return false;
        }

        try {
            // Wait for service worker registration
            this.swRegistration = await navigator.serviceWorker.register('/service-worker.js');
            console.log('[Notifications] Service Worker registered:', this.swRegistration);

            // Wait for the service worker to be ready
            await navigator.serviceWorker.ready;
            console.log('[Notifications] Service Worker is ready');
            
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
            return 'unsupported';
        }
        return Notification.permission;
    }

    async requestPermission() {
        try {
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
        if (!this.initialized || !this.swRegistration) {
            await this.init();
        }

        // Double check service worker is ready
        const registration = await navigator.serviceWorker.ready;
        if (registration !== this.swRegistration) {
            this.swRegistration = registration;
        }
        return this.swRegistration;
    }

    async showNotification(title, options = {}) {
        try {
            const registration = await this.ensureServiceWorkerReady();
            if (!registration) {
                console.error('[Notifications] Service Worker not ready');
                return false;
            }

            const permission = await this.checkPermission();
            if (permission !== 'granted') {
                console.log('[Notifications] Permission not granted');
                return false;
            }

            // Default options
            const defaultOptions = {
                icon: '/assets/images/icon-192x192.png',
                badge: '/assets/images/icon-96x96.png',
                vibrate: [200, 100, 200],
                requireInteraction: false,
                renotify: true
            };

            // Merge default options with provided options
            const notificationOptions = { ...defaultOptions, ...options };

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