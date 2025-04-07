<?php
$page_title = "Test Notification";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Notification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <h1>Test Notification</h1>
    <button onclick="testNotification()">Send Test Notification</button>

    <script>
    async function testNotification() {
        try {
            // Request permission
            const permission = await Notification.requestPermission();
            console.log('Permission:', permission);

            if (permission === 'granted') {
                // Register service worker
                const registration = await navigator.serviceWorker.register('/service-worker.js');
                console.log('SW registered:', registration);

                // Wait for service worker to be ready
                await navigator.serviceWorker.ready;
                console.log('SW ready');

                // Show notification
                await registration.showNotification('Test Notification', {
                    body: 'This is a test notification',
                    icon: '/assets/images/icon-192x192.png',
                    vibrate: [200, 100, 200]
                });
                console.log('Notification sent');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        }
    }
    </script>
</body>
</html> 