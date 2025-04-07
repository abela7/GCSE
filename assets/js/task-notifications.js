// Function to check for incomplete tasks and show notifications
function checkIncompleteTasks() {
    fetch('/GCSE/api/get_incomplete_tasks.php')
        .then(response => response.json())
        .then(data => {
            if (data.tasks && data.tasks.length > 0) {
                // Create notification text
                let notificationText = "You have not done:\n";
                data.tasks.forEach(task => {
                    notificationText += `- ${task.title}\n`;
                });
                notificationText += "\nDon't waste your time!";

                // Show notification
                if (Notification.permission === "granted") {
                    new Notification("Incomplete Tasks", {
                        body: notificationText,
                        icon: "/GCSE/assets/favicon/favicon.ico",
                        vibrate: [200, 100, 200]
                    });
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

// Function to show exam countdown notification
function showExamCountdown() {
    fetch('/GCSE/api/get_exam_countdown.php')
        .then(response => response.json())
        .then(data => {
            if (data.exams && data.exams.length > 0) {
                let notificationText = "Upcoming Exams:\n";
                data.exams.forEach(exam => {
                    notificationText += `${exam.subject}: ${exam.days_remaining} days remaining\n`;
                });

                if (Notification.permission === "granted") {
                    new Notification("Exam Countdown", {
                        body: notificationText,
                        icon: "/GCSE/assets/favicon/favicon.ico",
                        vibrate: [200, 100, 200]
                    });
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

// Function to show productive day message
function showProductiveDay() {
    if (Notification.permission === "granted") {
        new Notification("Good Morning! 🌟", {
            body: "Have a productive day ahead! Remember, every small step counts towards your success.",
            icon: "/GCSE/assets/favicon/favicon.ico",
            vibrate: [200, 100, 200]
        });
    }
}

// Request notification permission
function requestNotificationPermission() {
    if (!("Notification" in window)) {
        console.log("This browser does not support notifications");
        return;
    }

    if (Notification.permission !== "granted") {
        return Notification.requestPermission();
    }
    return Promise.resolve(Notification.permission);
}

// Function to show all notifications with delay
function showAllNotificationsWithDelay() {
    // Show notifications in sequence with delays
    setTimeout(() => {
        showExamCountdown();
        
        // Show productive day message after 2 seconds
        setTimeout(() => {
            showProductiveDay();
            
            // Show incomplete tasks after another 2 seconds
            setTimeout(() => {
                checkIncompleteTasks();
            }, 2000);
        }, 2000);
    }, 1000);
}

// Function to schedule notifications
function scheduleNotifications() {
    const now = new Date();
    
    // Schedule 7 AM exam countdown
    const examTime = new Date();
    examTime.setHours(7, 0, 0, 0);
    if (now > examTime) {
        examTime.setDate(examTime.getDate() + 1);
    }
    setTimeout(() => {
        showExamCountdown();
        // Schedule next day's exam countdown
        setInterval(showExamCountdown, 24 * 60 * 60 * 1000);
    }, examTime - now);

    // Schedule 9 AM productive day message
    const productiveTime = new Date();
    productiveTime.setHours(9, 0, 0, 0);
    if (now > productiveTime) {
        productiveTime.setDate(productiveTime.getDate() + 1);
    }
    setTimeout(() => {
        showProductiveDay();
        // Schedule next day's productive message
        setInterval(showProductiveDay, 24 * 60 * 60 * 1000);
    }, productiveTime - now);

    // Continue checking incomplete tasks every hour
    setInterval(checkIncompleteTasks, 3600000);
}

// Initialize notifications
document.addEventListener('DOMContentLoaded', () => {
    // Request permission and then show all notifications
    requestNotificationPermission().then((permission) => {
        if (permission === "granted") {
            // Show all notifications immediately with delays
            showAllNotificationsWithDelay();
            
            // Set up scheduled notifications
            scheduleNotifications();
        }
    });
}); 