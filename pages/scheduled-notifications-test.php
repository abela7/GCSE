<?php
require_once '../includes/auth_check.php';
require_once '../config/db_connect.php';
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

// Manually trigger notification if requested
$notification_sent = false;
$trigger_error = '';

if (isset($_POST['trigger_notification']) && isset($_POST['notification_type'])) {
    $notification_type = $_POST['notification_type'];
    
    try {
        switch ($notification_type) {
            case 'task':
                $output = shell_exec('php ../emails/cron/task_notifications.php 2>&1');
                $notification_sent = true;
                break;
            case 'habit':
                $output = shell_exec('php ../emails/cron/habit_notifications.php 2>&1');
                $notification_sent = true;
                break;
            case 'morning':
                $output = shell_exec('php ../emails/cron/morning_briefing.php 2>&1');
                $notification_sent = true;
                break;
            default:
                $trigger_error = "Invalid notification type";
        }
    } catch (Exception $e) {
        $trigger_error = "Error triggering notification: " . $e->getMessage();
    }
}

// Fetch notification history
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
    LIMIT 50
";

$notifications = [];
$stmt = $conn->prepare($notifications_query);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Fetch cron job status
$cron_output = shell_exec('crontab -l');
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

// Include common header
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
                                    <a href="../config/email_config.php" class="btn btn-success btn-sm" target="_blank">
                                        <i class="fas fa-edit me-1"></i> View Config File
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                        </div>
                    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h3 class="card-title"><i class="fas fa-history me-2"></i>Notification History</h3>
                </div>
                <div class="card-body">
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $notification): ?>
                                        <tr>
                                            <td><?= $notification['id'] ?></td>
                                            <td>
                                                <?php if ($notification['task_title']): ?>
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
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                        </div>
                    </div>

        <div class="col-md-5">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title"><i class="fas fa-envelope me-2"></i>Email Template Manager</h3>
                        </div>
                        <div class="card-body">
                    <?php if ($template_updated): ?>
                        <div class="alert alert-success">
                            <strong>Success!</strong> Template updated successfully.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($update_error): ?>
                        <div class="alert alert-danger">
                            <?= $update_error ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" id="template-form">
                        <div class="mb-3">
                            <label for="template_file" class="form-label">Select Template</label>
                            <select class="form-select" id="template_file" name="template_file" onchange="changeTemplate(this.value)">
                                <option value="task_notification.php" <?= $current_template == 'task_notification.php' ? 'selected' : '' ?>>
                                    Task Notification
                                </option>
                                <option value="habit_notification.php" <?= $current_template == 'habit_notification.php' ? 'selected' : '' ?>>
                                    Habit Notification
                                </option>
                                <option value="morning_briefing.php" <?= $current_template == 'morning_briefing.php' ? 'selected' : '' ?>>
                                    Morning Briefing
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="template_content" class="form-label">Template Code</label>
                            <textarea class="form-control" id="template_content" name="template_content" rows="20" style="font-family: monospace;"><?= htmlspecialchars($template_content) ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" name="save_template" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Template
                            </button>
                            
                            <a href="test_task_notification.php" class="btn btn-info" target="_blank">
                                <i class="fas fa-vial me-1"></i> Test Task Email
                            </a>
                            
                            <a href="test_habit_notification.php" class="btn btn-info" target="_blank">
                                <i class="fas fa-vial me-1"></i> Test Habit Email
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function changeTemplate(template) {
        window.location.href = 'scheduled-notifications-test.php?template=' + template;
    }
</script>

<?php
include '../includes/footer.php';
?> 