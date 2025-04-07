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
    </style>
</head>
<body>
    <h1>Test Notifications</h1>
    <button onclick="testNotification()">Show Pending Tasks Notification</button>
    <div id="result"></div>

    <script>
        function showResult(message, isError = false) {
            const resultDiv = document.getElementById('result');
            resultDiv.textContent = message;
            resultDiv.className = isError ? 'error' : 'success';
        }

        async function testNotification() {
            try {
                // Request notification permission if needed
                if (Notification.permission !== "granted") {
                    const permission = await Notification.requestPermission();
                    if (permission !== "granted") {
                        throw new Error("Notification permission denied");
                    }
                }

                // Register service worker if needed
                if (!('serviceWorker' in navigator)) {
                    throw new Error("Service Worker not supported");
                }

                const registration = await navigator.serviceWorker.register('/service-worker.js');
                
                // Fetch pending tasks
                const response = await fetch('/api/get_pending_tasks.php');
                const data = await response.json();

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
                    ]
                };

                // Show notification
                await registration.showNotification('Task Due Today', options);
                showResult(`Notification sent for task: ${nextTask.title}`);

            } catch (error) {
                console.error('Error:', error);
                showResult(error.message, true);
            }
        }
    </script>
</body>
</html> 