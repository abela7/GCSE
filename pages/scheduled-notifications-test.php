<?php
// Set page title
$page_title = "Notification System";

// Include database connection
require_once '../includes/db_connect.php';
require_once '../config/email_config.php';

// Execute manual notification script if requested
$script_executed = false;
$exec_output = '';
$exec_error = '';

if (isset($_GET['run_script']) && !empty($_GET['run_script'])) {
    // Get the absolute path for more reliable execution
    $base_path = realpath(__DIR__ . '/..');
    $script_name = $_GET['run_script'];
    
    // Whitelist of allowed scripts
    $allowed_scripts = [
        'morning_briefing.php',
        'task_notifications.php',
        'habit_notifications.php',
        'vocabulary_notifications.php',
        'mood_notifications.php'
    ];
    
    if (in_array($script_name, $allowed_scripts)) {
        try {
            // Use output buffering to capture any direct script output
            ob_start();
            include_once $base_path . '/emails/cron/' . $script_name;
            $exec_output = ob_get_clean();
            $script_executed = true;
        } catch (Exception $e) {
            $exec_error = "Error executing script: " . $e->getMessage();
        }
    } else {
        $exec_error = "Invalid script requested";
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
                    <?php if ($script_executed): ?>
                    <div class="alert alert-success">
                        <strong>Success!</strong> The notification script "<?= htmlspecialchars($_GET['run_script']) ?>" was executed successfully.
                        <?php if ($exec_output): ?>
                        <div class="mt-2">
                            <p><strong>Output:</strong></p>
                            <pre class="bg-light p-2 border rounded" style="max-height: 200px; overflow-y: auto;"><?= htmlspecialchars($exec_output) ?></pre>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($exec_error): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?= $exec_error ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Run Notifications Manually Section -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Run Notifications Manually</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Click any button below to open the notification script directly in your browser.
                                    </div>
                                    
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="../emails/cron/morning_briefing.php" target="_blank" class="btn btn-primary">
                                            <i class="fas fa-sun me-2"></i>Morning Briefing
                                        </a>
                                        <a href="../emails/cron/task_notifications.php" target="_blank" class="btn btn-danger">
                                            <i class="fas fa-tasks me-2"></i>Task Notifications
                                        </a>
                                        <a href="../emails/cron/habit_notifications.php" target="_blank" class="btn btn-warning">
                                            <i class="fas fa-sync-alt me-2"></i>Habit Notifications
                                        </a>
                                        <a href="../emails/cron/vocabulary_notifications.php" target="_blank" class="btn btn-info">
                                            <i class="fas fa-book me-2"></i>Vocabulary Notifications
                                        </a>
                                        <a href="../emails/cron/mood_notifications.php" target="_blank" class="btn btn-secondary">
                                            <i class="fas fa-smile me-2"></i>Mood Notifications
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notification History Section -->
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
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?> 