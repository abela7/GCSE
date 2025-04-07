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
            // Register service worker
            this.swRegistration = await navigator.serviceWorker.register('/service-worker.js');
            console.log('[Notifications] Service Worker registered:', this.swRegistration);
            
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
            return permission;
        } catch (error) {
            console.error('[Notifications] Permission request failed:', error);
            return 'denied';
        }
    }

    async showNotification(title, options = {}) {
        if (!this.initialized) {
            await this.init();
        }

        if (!this.swRegistration) {
            console.error('[Notifications] Service Worker not registered');
            return false;
        }

        try {
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

            await this.swRegistration.showNotification(title, notificationOptions);
            return true;
        } catch (error) {
            console.error('[Notifications] Show notification failed:', error);
            return false;
        }
    }

    // Test notifications
    async testTaskNotification() {
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