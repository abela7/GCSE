<?php
$page_title = "Test Notification";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Notification</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            margin: 10px 0;
        }
        #result {
            margin-top: 20px;
            padding: 10px;
            border-radius: 4px;
            white-space: pre-wrap;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        #debug {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Test Notifications</h1>
    <button onclick="testNotification()">Show Pending Tasks Notification</button>
    <div id="result"></div>
    <div id="debug"></div>

    <script>
        function showResult(message, isError = false, details = null) {
            const resultDiv = document.getElementById('result');
            resultDiv.textContent = message;
            resultDiv.className = isError ? 'error' : 'success';
            
            const debugDiv = document.getElementById('debug');
            if (details) {
                debugDiv.textContent = 'Debug Info:\n' + JSON.stringify(details, null, 2);
            } else {
                debugDiv.textContent = '';
            }
        }

        async function testNotification() {
            try {
                showResult('Checking notification permission...');
                
                // Check if notifications are supported
                if (!('Notification' in window)) {
                    throw new Error('Notifications are not supported in this browser');
                }

                // Request notification permission if needed
                if (Notification.permission !== "granted") {
                    showResult('Requesting notification permission...');
                    const permission = await Notification.requestPermission();
                    if (permission !== "granted") {
                        throw new Error("Notification permission denied");
                    }
                }

                showResult('Checking service worker...');
                
                // Check if service workers are supported
                if (!('serviceWorker' in navigator)) {
                    throw new Error("Service Worker not supported");
                }

                // Register service worker
                showResult('Registering service worker...');
                const registration = await navigator.serviceWorker.register('/service-worker.js');
                await navigator.serviceWorker.ready;
                
                // Fetch pending tasks
                showResult('Fetching pending tasks...');
                const response = await fetch('/api/get_pending_tasks.php');
                const data = await response.json();
                
                showResult('Processing response...', false, data);

                if (!data.success) {
                    throw new Error(data.message || "Failed to fetch tasks");
                }

                if (data.tasks.length === 0) {
                    showResult("No pending tasks for today!");
                    return;
                }

                // Create notification content
                const nextTask = data.tasks[0];
                const options = {
                    body: `You have to ${nextTask.title} next`,
                    icon: '/assets/icons/icon-192x192.png',
                    badge: '/assets/icons/badge-96x96.png',
                    tag: 'pending-tasks',
                    data: {
                        url: '/pages/tasks/index.php',
                        taskId: nextTask.id
                    },
                    actions: [
                        {
                            action: 'view',
                            title: 'View Tasks'
                        }
                    ],
                    requireInteraction: true,
                    renotify: true
                };

                // Show notification
                showResult('Showing notification...');
                await registration.showNotification('Task Due Today', options);
                showResult(`Notification sent for task: ${nextTask.title}`, false, {
                    notificationOptions: options,
                    serviceWorkerState: registration.active ? 'active' : 'inactive'
                });

            } catch (error) {
                console.error('Error:', error);
                showResult(error.message, true, {
                    errorName: error.name,
                    errorStack: error.stack,
                    notificationPermission: Notification.permission,
                    serviceWorkerSupported: 'serviceWorker' in navigator
                });
            }
        }

        // Check initial state
        window.addEventListener('load', async () => {
            try {
                const initialState = {
                    notificationPermission: Notification.permission,
                    serviceWorkerSupported: 'serviceWorker' in navigator,
                };
                
                if ('serviceWorker' in navigator) {
                    const registration = await navigator.serviceWorker.getRegistration();
                    initialState.serviceWorkerRegistered = !!registration;
                    initialState.serviceWorkerState = registration?.active ? 'active' : 'inactive';
                }
                
                showResult('Ready to test notifications', false, initialState);
            } catch (error) {
                console.error('Error checking initial state:', error);
            }
        });
    </script>
</body>
</html> 