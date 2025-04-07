// Function to check for incomplete tasks and show notifications
function checkIncompleteTasks() {
    console.log('Checking for incomplete tasks...');
    fetch('/api/get_incomplete_tasks.php')
        .then(response => response.json())
        .then(data => {
            console.log('Incomplete tasks response:', data);
            if (data.tasks && data.tasks.length > 0) {
                // Create notification text
                let notificationText = "You have not done:\n";
                data.tasks.forEach(task => {
                    notificationText += `- ${task.title}\n`;
                });
                notificationText += "\nDon't waste your time!";

                // Show notification
                if (Notification.permission === "granted") {
                    new Notification("Web-App Tasks", {
                        body: notificationText,
                        icon: "/assets/favicon/favicon.ico",
                        vibrate: [200, 100, 200]
                    });
                }
            }
        })
        .catch(error => console.error('Error checking tasks:', error));
}

// Function to show exam countdown notification
function showExamCountdown() {
    console.log('Checking exam countdown...');
    fetch('/api/get_exam_countdown.php')
        .then(response => response.json())
        .then(data => {
            console.log('Exam countdown response:', data);
            if (data.exams && data.exams.length > 0) {
                let notificationText = "Upcoming Exams:\n";
                data.exams.forEach(exam => {
                    notificationText += `${exam.subject}: ${exam.days_remaining} days remaining\n`;
                });

                if (Notification.permission === "granted") {
                    new Notification("Web-App Exams", {
                        body: notificationText,
                        icon: "/assets/favicon/favicon.ico",
                        vibrate: [200, 100, 200]
                    });
                }
            }
        })
        .catch(error => console.error('Error checking exams:', error));
}

// Function to show productive day message
function showProductiveDay() {
    console.log('Showing productive day message...');
    if (Notification.permission === "granted") {
        new Notification("Good Morning! 🌟", {
            body: "Have a productive day ahead! Remember, every small step counts towards your success.",
            icon: "/assets/favicon/favicon.ico",
            vibrate: [200, 100, 200]
        });
    }
}

// Request notification permission
function requestNotificationPermission() {
    console.log('Requesting notification permission...');
    if (!("Notification" in window)) {
        console.log("This browser does not support notifications");
        return Promise.reject("Notifications not supported");
    }

    console.log('Current notification permission:', Notification.permission);
    if (Notification.permission !== "granted") {
        return Notification.requestPermission().then(permission => {
            console.log('Permission response:', permission);
            return permission;
        });
    }
    return Promise.resolve(Notification.permission);
}

// Function to show all notifications with delay
function showAllNotificationsWithDelay() {
    console.log('Showing all notifications with delay...');
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
    console.log('Scheduling notifications...');
    const now = new Date();
    
    // Schedule 7 AM exam countdown
    const examTime = new Date();
    examTime.setHours(7, 0, 0, 0);
    if (now > examTime) {
        examTime.setDate(examTime.getDate() + 1);
    }
    const examDelay = examTime - now;
    console.log(`Exam notification scheduled for: ${examTime} (in ${examDelay}ms)`);
    
    setTimeout(() => {
        showExamCountdown();
        // Schedule next day's exam countdown
        setInterval(showExamCountdown, 24 * 60 * 60 * 1000);
    }, examDelay);

    // Schedule 9 AM productive day message
    const productiveTime = new Date();
    productiveTime.setHours(9, 0, 0, 0);
    if (now > productiveTime) {
        productiveTime.setDate(productiveTime.getDate() + 1);
    }
    const productiveDelay = productiveTime - now;
    console.log(`Productive message scheduled for: ${productiveTime} (in ${productiveDelay}ms)`);
    
    setTimeout(() => {
        showProductiveDay();
        // Schedule next day's productive message
        setInterval(showProductiveDay, 24 * 60 * 60 * 1000);
    }, productiveDelay);

    // Continue checking incomplete tasks every hour
    setInterval(checkIncompleteTasks, 3600000);
}

// Function to check if notifications are enabled for a specific type
async function isNotificationEnabled(type) {
    try {
        const response = await fetch('/api/get_notification_settings.php');
        const data = await response.json();
        
        if (data.success) {
            return data.settings[type] === 1;
        }
        return false;
    } catch (error) {
        console.error('Error checking notification settings:', error);
        return false;
    }
}

// Function to show task reminder notification
async function showTaskReminderNotification() {
    if (!await isNotificationEnabled('task_reminders')) return;
    
    try {
        const response = await fetch('/api/get_incomplete_tasks.php');
        const data = await response.json();
        
        if (data.success && data.tasks.length > 0) {
            const task = data.tasks[0];
            new Notification("Task Reminder", {
                body: `You have an incomplete task: ${task.title}`,
                icon: '/assets/images/icon-192x192.png'
            });
        }
    } catch (error) {
        console.error('Error showing task reminder:', error);
    }
}

// Function to show exam countdown notification
async function showExamCountdownNotification() {
    if (!await isNotificationEnabled('exam_reminders')) return;
    
    try {
        const response = await fetch('/api/get_exam_countdown.php');
        const data = await response.json();
        
        if (data.success && data.exams.length > 0) {
            const exam = data.exams[0];
            new Notification("Exam Countdown", {
                body: `Upcoming exam: ${exam.subject} in ${exam.days_until} days`,
                icon: '/assets/images/icon-192x192.png'
            });
        }
    } catch (error) {
        console.error('Error showing exam countdown:', error);
    }
}

// Function to show daily motivation notification
async function showDailyMotivationNotification() {
    if (!await isNotificationEnabled('daily_motivation')) return;
    
    try {
        const response = await fetch('/api/get_motivational_message.php');
        const data = await response.json();
        
        if (data.success) {
            new Notification("Daily Motivation", {
                body: data.message,
                icon: '/assets/images/icon-192x192.png'
            });
        }
    } catch (error) {
        console.error('Error showing motivation message:', error);
    }
}

// Initialize notifications
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing notifications...');
    // Request permission and then show all notifications
    requestNotificationPermission().then((permission) => {
        console.log('Permission status:', permission);
        if (permission === "granted") {
            // Show all notifications immediately with delays
            showAllNotificationsWithDelay();
            
            // Set up scheduled notifications
            scheduleNotifications();
        }
    }).catch(error => {
        console.error('Error setting up notifications:', error);
    });
}); 