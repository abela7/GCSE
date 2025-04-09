<?php
// Set page title
$page_title = "Notification System";

// Include database connection
require_once '../includes/db_connect.php';
require_once '../config/email_config.php';

// Process template edits if submitted
$template_updated = false;
$update_error = '';

if (isset($_POST['save_template']) && isset($_POST['template_content']) && isset($_POST['template_file'])) {
    $template_file = $_POST['template_file'];
    $template_content = $_POST['template_content'];
    
    // Validate template file path
    $allowed_templates = [
        'task_notification.php' => '../emails/templates/task_notification.php',
        'habit_notification.php' => '../emails/templates/habit_notification.php',
        'morning_briefing.php' => '../emails/templates/morning_briefing.php'
    ];
    
    if (array_key_exists($template_file, $allowed_templates)) {
        $file_path = $allowed_templates[$template_file];
        
        // Write updated template
        try {
            file_put_contents($file_path, $template_content);
            $template_updated = true;
        } catch (Exception $e) {
            $update_error = "Error updating template: " . $e->getMessage();
        }
    } else {
        $update_error = "Invalid template file selected";
    }
}

// Handle individual notification deletion
$delete_success = false;
$delete_error = '';

if (isset($_POST['delete_notification']) && isset($_POST['notification_id'])) {
    $notification_id = intval($_POST['notification_id']);
    try {
        $delete_query = "DELETE FROM task_notification_tracking WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $notification_id);
        if ($stmt->execute()) {
            $delete_success = true;
        } else {
            $delete_error = "Error deleting notification: " . $conn->error;
        }
    } catch (Exception $e) {
        $delete_error = "Error deleting notification: " . $e->getMessage();
    }
}

// Handle history deletion if requested
if (isset($_POST['delete_history'])) {
    try {
        $delete_query = "TRUNCATE TABLE task_notification_tracking";
        if ($conn->query($delete_query)) {
            $delete_success = true;
        } else {
            $delete_error = "Error clearing notification history: " . $conn->error;
        }
    } catch (Exception $e) {
        $delete_error = "Error clearing notification history: " . $e->getMessage();
    }
}

// Send test notification if requested
$test_sent = false;
$test_error = '';
$test_output = '';

if (isset($_POST['send_test_notification'])) {
    try {
        // Get the absolute path for more reliable execution
        $base_path = realpath(__DIR__ . '/..');
        $notification_type = isset($_POST['test_notification_type']) ? $_POST['test_notification_type'] : 'task';
        
        if ($notification_type === 'task') {
            $test_output = shell_exec('php ' . $base_path . '/test_task_notification.php 2>&1');
        } else {
            $test_output = shell_exec('php ' . $base_path . '/test_habit_notification.php 2>&1');
        }
        
        $test_sent = true;
        
        // Check if there's a spam warning in the output
        if (strpos($test_output, 'SPAM') !== false) {
            $test_error = "The email server classified the message as SPAM. Please review the email content and try the anti-spam measures below:";
        }
    } catch (Exception $e) {
        $test_error = "Error sending test notification: " . $e->getMessage();
    }
}

// Manually trigger notification if requested
$notification_sent = false;
$trigger_error = '';
$output = '';

if (isset($_POST['trigger_notification']) && isset($_POST['notification_type'])) {
    $notification_type = $_POST['notification_type'];
    
    try {
        // Get the absolute path for more reliable execution
        $base_path = realpath(__DIR__ . '/..');
        
        switch ($notification_type) {
            case 'task':
                $output = shell_exec('php ' . $base_path . '/emails/cron/task_notifications.php 2>&1');
                $notification_sent = true;
                break;
            case 'habit':
                $output = shell_exec('php ' . $base_path . '/emails/cron/habit_notifications.php 2>&1');
                $notification_sent = true;
                break;
            case 'morning':
                $output = shell_exec('php ' . $base_path . '/emails/cron/morning_briefing.php 2>&1');
                $notification_sent = true;
                break;
            default:
                $trigger_error = "Invalid notification type";
        }
    } catch (Exception $e) {
        $trigger_error = "Error triggering notification: " . $e->getMessage();
    }
}

// Set fixed limit for initial display
$limit = 5;
if (isset($_GET['load_more'])) {
    $limit = 5 + (intval($_GET['load_more']) * 10);
}

// Fetch notification history - initially just 5
$notifications_query = "
    SELECT 
        tnt.id,
        tnt.task_id,
        tnt.notification_type,
        tnt.sent_at,
        t.title as task_title
    FROM 
        task_notification_tracking tnt
    LEFT JOIN 
        tasks t ON tnt.task_id = t.id
    ORDER BY 
        tnt.sent_at DESC
    LIMIT ?
";

$notifications = [];
$stmt = $conn->prepare($notifications_query);
$stmt->bind_param("i", $limit);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM task_notification_tracking";
$total_count = 0;
$result = $conn->query($count_query);
if ($row = $result->fetch_assoc()) {
    $total_count = $row['total'];
}

// Fetch cron job status - using direct command instead of caching
$cron_output = shell_exec('crontab -l 2>&1');
$task_cron_active = strpos($cron_output, 'task_notifications.php') !== false;
$habit_cron_active = strpos($cron_output, 'habit_notifications.php') !== false;
$morning_cron_active = strpos($cron_output, 'morning_briefing.php') !== false;

// Template management - load template files
$template_files = [
    'task_notification.php' => '../emails/templates/task_notification.php',
    'habit_notification.php' => '../emails/templates/habit_notification.php',
    'morning_briefing.php' => '../emails/templates/morning_briefing.php'
];

$current_template = isset($_GET['template']) && array_key_exists($_GET['template'], $template_files) 
    ? $_GET['template'] 
    : 'task_notification.php';

$template_content = file_get_contents($template_files[$current_template]);

// Include header
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title"><i class="fas fa-bell me-2"></i>Notification System Dashboard</h3>
                </div>
                <div class="card-body">
                    <!-- Notification History Section (Moved up) -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Notification History</h5>
                                    <form method="post" onsubmit="return confirm('Are you sure you want to delete all notification history? This action cannot be undone.');" class="m-0">
                                        <button type="submit" name="delete_history" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash-alt me-1"></i> Delete All History
                                        </button>
                                    </form>
                                </div>
                                <div class="card-body">
                                    <?php if ($delete_success): ?>
                                        <div class="alert alert-success">
                                            Notification record has been successfully deleted.
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($delete_error): ?>
                                        <div class="alert alert-danger">
                                            <?= $delete_error ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (empty($notifications)): ?>
                                        <div class="alert alert-info">
                                            No notification records found.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Task/Habit</th>
                                                        <th>Type</th>
                                                        <th>Sent At</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($notifications as $notification): ?>
                                                        <tr>
                                                            <td><?= $notification['id'] ?></td>
                                                            <td>
                                                                <?php if (isset($notification['task_title']) && $notification['task_title']): ?>
                                                                    <?= htmlspecialchars($notification['task_title']) ?>
                                                                <?php else: ?>
                                                                    <span class="text-muted">ID: <?= $notification['task_id'] ?></span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $badge_class = 'bg-secondary';
                                                                switch ($notification['notification_type']) {
                                                                    case 'due':
                                                                        $badge_class = 'bg-danger';
                                                                        break;
                                                                    case 'reminder':
                                                                        $badge_class = 'bg-warning text-dark';
                                                                        break;
                                                                    case 'habit':
                                                                        $badge_class = 'bg-primary';
                                                                        break;
                                                                }
                                                                ?>
                                                                <span class="badge <?= $badge_class ?>"><?= $notification['notification_type'] ?></span>
                                                            </td>
                                                            <td><?= $notification['sent_at'] ?></td>
                                                            <td>
                                                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this notification record?');">
                                                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                                    <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger" title="Delete Record">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <?php if ($total_count > $limit): ?>
                                            <div class="text-center mt-3">
                                                <?php $load_more_count = isset($_GET['load_more']) ? intval($_GET['load_more']) + 1 : 1; ?>
                                                <a href="?load_more=<?= $load_more_count ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-sync me-1"></i> Load More History (showing <?= count($notifications) ?> of <?= $total_count ?>)
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Rest of the dashboard cards -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header bg-info text-white">
                                    <h5><i class="fas fa-clock me-2"></i>Cron Job Status</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Task Notifications
                                            <?php if ($task_cron_active): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Found</span>
                                            <?php endif; ?>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Habit Notifications
                                            <?php if ($habit_cron_active): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Found</span>
                                            <?php endif; ?>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Morning Briefing
                                            <?php if ($morning_cron_active): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Found</span>
                                            <?php endif; ?>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">Last checked: <?= date('Y-m-d H:i:s') ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header bg-warning text-dark">
                                    <h5><i class="fas fa-paper-plane me-2"></i>Trigger Notifications</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($notification_sent): ?>
                                        <div class="alert alert-success">
                                            <strong>Success!</strong> Notification script executed.
                                            <pre class="mt-2 border p-2 bg-light" style="max-height: 200px; overflow-y: auto;"><?= htmlspecialchars($output) ?></pre>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($trigger_error): ?>
                                        <div class="alert alert-danger">
                                            <?= $trigger_error ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="notification_type" class="form-label">Notification Type</label>
                                            <select class="form-select" id="notification_type" name="notification_type" required>
                                                <option value="task">Task Notifications</option>
                                                <option value="habit">Habit Notifications</option>
                                                <option value="morning">Morning Briefing</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="trigger_notification" class="btn btn-warning">
                                            <i class="fas fa-play-circle me-1"></i> Trigger Now
                                        </button>
                                    </form>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">This will manually execute the notification script</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header bg-purple text-white" style="background-color: #6f42c1;">
                                    <h5><i class="fas fa-vial me-2"></i>Test Notifications</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($test_sent): ?>
                                        <div class="alert alert-success">
                                            <strong>Success!</strong> Test notification sent.
                                            <pre class="mt-2 border p-2 bg-light" style="max-height: 200px; overflow-y: auto;"><?= htmlspecialchars($test_output) ?></pre>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($test_error): ?>
                                        <div class="alert alert-danger">
                                            <strong>Error:</strong> <?= $test_error ?>
                                            
                                            <?php if (strpos($test_error, 'SPAM') !== false): ?>
                                                <hr>
                                                <h6>Anti-Spam Recommendations:</h6>
                                                <ol class="small">
                                                    <li>Avoid using all caps in the subject or body</li>
                                                    <li>Don't use excessive exclamation marks</li>
                                                    <li>Ensure there's a plain text version of the email</li>
                                                    <li>Use a proper From address with a valid domain</li>
                                                    <li>Consider configuring SPF, DKIM, and DMARC records</li>
                                                    <li>Add the sending email to your contacts list</li>
                                                </ol>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="alert alert-info mb-3">
                                        <h6><i class="fas fa-info-circle me-1"></i> Notification Timing Update</h6>
                                        <p class="small mb-0">Task notifications are now configured to send exactly when tasks are due (±1 minute) instead of 5 minutes before. Anti-spam measures have also been added to improve delivery rates.</p>
                                    </div>
                                    
                                    <div class="alert alert-primary mb-3">
                                        <h6><i class="fas fa-envelope me-1"></i> Recipient Email Updated</h6>
                                        <p class="small mb-0">Notification emails are now sent to <strong><?= SMTP_USERNAME ?></strong> instead of a hardcoded Gmail address. This matches the test notification settings.</p>
                                    </div>
                                    
                                    <p class="text-muted mb-3">Send test notifications with sample data to verify email formatting</p>
                                    <form method="post">
                                        <div class="mb-3">
                                            <label for="test_notification_type" class="form-label">Notification Type</label>
                                            <select class="form-select" id="test_notification_type" name="test_notification_type" required>
                                                <option value="task">Task Notifications</option>
                                                <option value="habit">Habit Notifications</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="send_test_notification" class="btn btn-primary w-100">
                                            <i class="fas fa-envelope me-1"></i> Send Test Email
                                        </button>
                                    </form>
                                    
                                    <hr>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="../test_task_notification.php" target="_blank" class="btn btn-outline-secondary">
                                            <i class="fas fa-external-link-alt me-1"></i> View Test Script
                                        </a>
                                        <a href="../test_habit_notification.php" target="_blank" class="btn btn-outline-secondary">
                                            <i class="fas fa-external-link-alt me-1"></i> View Habit Test Script
                                        </a>
                                        
                                        <a href="../emails/cron/task_notifications.php" target="_blank" class="btn btn-outline-primary mt-2">
                                            <i class="fas fa-code me-1"></i> Run Task Notification Script Directly
                                        </a>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">These test emails contain sample data</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-12 mt-3">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white">
                                    <h5><i class="fas fa-cog me-2"></i>Configuration</h5>
                                </div>
                                <div class="card-body">
                                    <h6>Email Settings</h6>
                                    <ul class="list-group mb-3">
                                        <li class="list-group-item">
                                            <strong>SMTP Host:</strong> <?= SMTP_HOST ?>
                                        </li>
                                        <li class="list-group-item">
                                            <strong>SMTP Port:</strong> <?= SMTP_PORT ?>
                                        </li>
                                        <li class="list-group-item">
                                            <strong>Email From:</strong> <?= EMAIL_FROM_ADDRESS ?>
                                        </li>
                                        <li class="list-group-item">
                                            <strong>Notifications:</strong>
                                            <?= ENABLE_EMAIL_NOTIFICATIONS ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-danger">Disabled</span>' ?>
                                        </li>
                                    </ul>
                                    <div class="d-flex gap-2">
                                        <a href="../config/email_config.php" class="btn btn-success btn-sm" target="_blank">
                                            <i class="fas fa-edit me-1"></i> View Config
                                        </a>
                                        <a href="../test_email_delivery.php" class="btn btn-warning btn-sm" target="_blank">
                                            <i class="fas fa-wrench me-1"></i> Email Diagnostic Tool
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Refresh page every 5 minutes to keep cron status up-to-date
    // But preserve the load_more parameter
    setTimeout(function() {
        const currentLoadMore = new URLSearchParams(window.location.search).get('load_more') || '';
        let newUrl = window.location.pathname;
        if (currentLoadMore) {
            newUrl += '?load_more=' + currentLoadMore;
        }
        window.location.href = newUrl;
    }, 300000); // 5 minutes
</script>

<?php
// Include footer
include '../includes/footer.php';
?> 