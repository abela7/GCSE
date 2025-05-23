<?php
// Set timezone to London
date_default_timezone_set('Europe/London');

require_once '../../includes/header.php';
require_once '../../includes/db_connect.php';

// Handle POST actions for tasks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $conn->begin_transaction();
        
        $task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
        $action = $_POST['action'];
        
        // Get task type first
        $stmt = $conn->prepare("SELECT task_type FROM tasks WHERE id = ?");
        $stmt->bind_param('i', $task_id);
        $stmt->execute();
        $task = $stmt->get_result()->fetch_assoc();
        
        if ($task) {
            switch ($action) {
                case 'done':
                    if ($task['task_type'] === 'one-time') {
                        // Update one-time task
                        $stmt = $conn->prepare("UPDATE tasks SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->bind_param('i', $task_id);
                        $stmt->execute();
                    } else {
                        // Update recurring task instance for today
                        $stmt = $conn->prepare("UPDATE task_instances SET status = 'completed', updated_at = CURRENT_TIMESTAMP 
                                              WHERE task_id = ? AND due_date = CURRENT_DATE");
                        $stmt->bind_param('i', $task_id);
                        $stmt->execute();
                    }
                    break;

                case 'snooze':
                    $snooze_minutes = isset($_POST['snooze_minutes']) ? (int)$_POST['snooze_minutes'] : 30;
                    
                    if ($task['task_type'] === 'one-time') {
                        // Get current due time
                        $stmt = $conn->prepare("SELECT due_time FROM tasks WHERE id = ?");
                        $stmt->bind_param('i', $task_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $current = $result->fetch_assoc();
                        
                        // Calculate new time
                        $new_time = date('H:i:s', strtotime($current['due_time'] . " +{$snooze_minutes} minutes"));
                        
                        // Update task
                        $stmt = $conn->prepare("UPDATE tasks SET due_time = ?, status = 'snoozed', 
                                              updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->bind_param('si', $new_time, $task_id);
                        $stmt->execute();
                    } else {
                        // Get current instance due time
                        $stmt = $conn->prepare("SELECT due_time FROM task_instances 
                                              WHERE task_id = ? AND due_date = CURRENT_DATE");
                        $stmt->bind_param('i', $task_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $current = $result->fetch_assoc();
                        
                        // Calculate new time
                        $new_time = date('H:i:s', strtotime($current['due_time'] . " +{$snooze_minutes} minutes"));
                        
                        // Update instance
                        $stmt = $conn->prepare("UPDATE task_instances SET due_time = ?, status = 'snoozed', 
                                              updated_at = CURRENT_TIMESTAMP 
                                              WHERE task_id = ? AND due_date = CURRENT_DATE");
                        $stmt->bind_param('si', $new_time, $task_id);
                        $stmt->execute();
                    }
                    break;

                case 'not_done':
                    if ($task['task_type'] === 'one-time') {
                        // Update one-time task
                        $stmt = $conn->prepare("UPDATE tasks SET status = 'not_done', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->bind_param('i', $task_id);
                        $stmt->execute();
                    } else {
                        // Update recurring task instance for today
                        $stmt = $conn->prepare("UPDATE task_instances SET status = 'not_done', updated_at = CURRENT_TIMESTAMP 
                                              WHERE task_id = ? AND due_date = CURRENT_DATE");
                        $stmt->bind_param('i', $task_id);
                        $stmt->execute();
                    }
                    break;
            }
        }
        
        $conn->commit();
        $_SESSION['success'] = "Task updated successfully";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error updating task: " . $e->getMessage();
    }
    
    // Redirect back to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['date']) ? "?date=" . $_GET['date'] : ""));
    exit;
}

// Get current hour for greeting
$hour = date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Good Morning';
    $icon = 'fa-sun';
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = 'Good Afternoon';
    $icon = 'fa-cloud-sun';
} elseif ($hour >= 17 && $hour < 21) {
    $greeting = 'Good Evening';
    $icon = 'fa-moon';
} else {
    $greeting = 'Good Night';
    $icon = 'fa-moon';
}

// Get selected date from URL parameter or use today
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$dateObj = new DateTime($selectedDate);
$prevDate = (clone $dateObj)->modify('-1 day')->format('Y-m-d');
$nextDate = (clone $dateObj)->modify('+1 day')->format('Y-m-d');

// Get tasks for the selected date
$query = "SELECT 
            t.id, t.title, t.description, t.due_date, t.due_time, t.task_type, t.priority,
            c.name as category_name, c.icon as category_icon, c.color as category_color,
            COALESCE(ti.status, t.status) as status
          FROM tasks t
          JOIN task_categories c ON t.category_id = c.id
          LEFT JOIN task_instances ti ON t.id = ti.task_id 
              AND ti.due_date = ?
              AND ti.status = 'pending'
          WHERE t.is_active = 1
          AND (
              (t.task_type = 'one-time' AND t.status = 'pending' AND t.due_date = ?)
              OR 
              (t.task_type = 'recurring' AND ti.id IS NOT NULL)
          )
          ORDER BY t.due_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param('ss', $selectedDate, $selectedDate);
$stmt->execute();
$result = $stmt->get_result();

$morning_tasks = [];
$evening_tasks = [];

while ($task = $result->fetch_assoc()) {
    $task_hour = date('H', strtotime($task['due_time']));
    if ($task_hour < 12) {
        $morning_tasks[] = $task;
    } else {
        $evening_tasks[] = $task;
    }
}
?>

<div class="container-fluid">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex">
                
                <div class="date-navigation d-flex mb-3 gap-2">
                    <a href="?date=<?php echo $prevDate; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <span class="date-display">
                        <?php echo $dateObj->format('l, F j, Y'); ?>
                    </span>
                    <a href="?date=<?php echo $nextDate; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <a href="manage_tasks.php" class="btn btn-outline-primary">
                    <i class="fas fa-tasks"></i>
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                    <i class="fas fa-plus"></i>
                </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Greeting Section -->
    <div class="greeting-section">
        <div class="greeting-container">
            <div class="greeting-left">
                <div class="greeting-icon">
                    <i class="fas <?php echo $icon; ?>"></i>
                </div>
                <span class="greeting-text"><?php echo $greeting; ?></span>
            </div>
            <div class="greeting-actions">
                <a href="manage_tasks.php" class="action-btn settings-btn">
                    <i class="fas fa-cog"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Morning Tasks Section -->
        <div class="col-lg-6">
            <div class="section-header">
                <i class="fas fa-sun" style="color: #f39c12;"></i>
                <span>Morning Tasks</span>
            </div>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($morning_tasks as $task): ?>
                <div class="task-card">
                    <div class="task-left">
                        <div class="task-icon">
                            <i class="<?php echo $task['category_icon']; ?>"></i>
                        </div>
                        <div class="task-content">
                            <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                            <div class="task-meta">
                                <?php echo htmlspecialchars($task['category_name']); ?> • 
                                <time>
                                    <?php 
                                    $dueDateTime = new DateTime($task['due_date'] . ' ' . $task['due_time']);
                                    echo $dueDateTime->format('D') . ', ' . date('g:i A', strtotime($task['due_time'])); 
                                    ?>
                                </time>
                            </div>
                        </div>
                    </div>
                    <div class="task-actions">
                        <button type="button" 
                                class="action-btn done-btn" 
                                onclick="handleTaskAction(<?php echo $task['id']; ?>, 'done')"
                                title="Mark as Done">
                            <i class="fas fa-check"></i>
                        </button>
                        <?php
                        $dueDateTime = new DateTime($task['due_date'] . ' ' . $task['due_time']);
                        $now = new DateTime();
                        $isSnoozeEnabled = $dueDateTime <= $now;
                        ?>
                        <button type="button" 
                                class="action-btn snooze-btn" 
                                onclick="handleTaskAction(<?php echo $task['id']; ?>, 'snooze')"
                                title="Snooze Task"
                                style="display: <?php echo $isSnoozeEnabled ? 'flex' : 'none'; ?>"
                                data-task-id="<?php echo $task['id']; ?>"
                                data-due-time="<?php echo $task['due_time']; ?>"
                                data-due-date="<?php echo $task['due_date']; ?>">
                            <i class="fas fa-clock"></i>
                        </button>
                        <button type="button" 
                                class="action-btn cancel-btn" 
                                onclick="handleTaskAction(<?php echo $task['id']; ?>, 'cancel')"
                                title="Cancel Task">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Evening Tasks Section -->
        <div class="col-lg-6">
            <div class="section-header">
                <i class="fas fa-moon" style="color: #2c3e50;"></i>
                <span>Evening Tasks</span>
            </div>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($evening_tasks as $task): ?>
                <div class="task-card">
                    <div class="task-left">
                        <div class="task-icon">
                            <i class="<?php echo $task['category_icon']; ?>"></i>
                        </div>
                        <div class="task-content">
                            <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                            <div class="task-meta">
                                <?php echo htmlspecialchars($task['category_name']); ?> • 
                                <time>
                                    <?php 
                                    $dueDateTime = new DateTime($task['due_date'] . ' ' . $task['due_time']);
                                    echo $dueDateTime->format('D') . ', ' . date('g:i A', strtotime($task['due_time'])); 
                                    ?>
                                </time>
                            </div>
                        </div>
                    </div>
                    <div class="task-actions">
                        <button type="button" 
                                class="action-btn done-btn" 
                                onclick="handleTaskAction(<?php echo $task['id']; ?>, 'done')"
                                title="Mark as Done">
                            <i class="fas fa-check"></i>
                        </button>
                        <?php
                        $dueDateTime = new DateTime($task['due_date'] . ' ' . $task['due_time']);
                        $now = new DateTime();
                        $isSnoozeEnabled = $dueDateTime <= $now;
                        ?>
                        <button type="button" 
                                class="action-btn snooze-btn" 
                                onclick="handleTaskAction(<?php echo $task['id']; ?>, 'snooze')"
                                title="Snooze Task"
                                style="display: <?php echo $isSnoozeEnabled ? 'flex' : 'none'; ?>"
                                data-task-id="<?php echo $task['id']; ?>"
                                data-due-time="<?php echo $task['due_time']; ?>"
                                data-due-date="<?php echo $task['due_date']; ?>">
                            <i class="fas fa-clock"></i>
                        </button>
                        <button type="button" 
                                class="action-btn cancel-btn" 
                                onclick="handleTaskAction(<?php echo $task['id']; ?>, 'cancel')"
                                title="Cancel Task">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Snooze Modal -->
<div class="modal fade" id="snoozeModal" tabindex="-1" aria-labelledby="snoozeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="snoozeModalLabel">Snooze Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="snoozeTaskInfo" class="mb-4">
                    <!-- Task info will be inserted here -->
                </div>
                <div id="snoozeOptions" class="d-flex flex-column gap-2">
                    <button type="button" class="btn btn-outline-warning text-start" onclick="handleSnoozeSelection(10)">
                        <i class="fas fa-clock me-2"></i>10 minutes
                    </button>
                    <button type="button" class="btn btn-outline-warning text-start" onclick="handleSnoozeSelection(30)">
                        <i class="fas fa-clock me-2"></i>30 minutes
                    </button>
                    <button type="button" class="btn btn-outline-warning text-start" onclick="handleSnoozeSelection(60)">
                        <i class="fas fa-clock me-2"></i>1 hour
                    </button>
                    <button type="button" class="btn btn-outline-warning text-start" onclick="handleSnoozeSelection(120)">
                        <i class="fas fa-clock me-2"></i>2 hours
                    </button>
                    <button type="button" class="btn btn-outline-warning text-start" onclick="handleSnoozeSelection(300)">
                        <i class="fas fa-clock me-2"></i>5 hours
                    </button>
                </div>
                <div id="editTaskOption" class="mt-3 text-center" style="display: none;">
                    <button type="button" class="btn btn-primary" onclick="openEditTask()">
                        <i class="fas fa-edit me-2"></i>Edit Task
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTaskModalLabel">Add New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="save_task.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <?php
                                $categories_query = "SELECT * FROM task_categories ORDER BY name";
                                $categories_result = $conn->query($categories_query);
                                while ($category = $categories_result->fetch_assoc()):
                                ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required 
                                value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="due_time" class="form-label">Due Time</label>
                            <input type="time" class="form-control" id="due_time" name="due_time">
                        </div>
                        <div class="col-md-6">
                            <label for="task_type" class="form-label">Task Type</label>
                            <select class="form-select" id="task_type" name="task_type" required>
                                <option value="one-time">One-time</option>
                                <option value="recurring">Recurring</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="estimated_duration" class="form-label">Estimated Duration (minutes)</label>
                            <input type="number" class="form-control" id="estimated_duration" name="estimated_duration" min="1" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
    <?php 
    echo $_SESSION['message'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<style>
:root {
    --primary-color: #cdaf56;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --text-muted: #6c757d;
    --border-radius: 12px;
    --transition-speed: 0.2s;
    --card-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.card {
    transition: all var(--transition-speed) ease;
    border-radius: var(--border-radius) !important;
    box-shadow: var(--card-shadow);
    background: white;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.card.border-success {
    border-color: var(--success-color) !important;
    background: linear-gradient(to right, rgba(40, 167, 69, 0.05), white) !important;
}

.card.border-warning {
    border-color: var(--warning-color) !important;
    background: linear-gradient(to right, rgba(255, 193, 7, 0.05), white) !important;
}

.card.border-danger {
    border-color: var(--danger-color) !important;
    background: linear-gradient(to right, rgba(220, 53, 69, 0.05), white) !important;
}

/* Task Icon */
.task-icon {
    width: 48px;
    height: 48px;
    min-width: 48px;
    background: #f8f9fa;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: inherit;
}

.task-icon i {
    transition: all var(--transition-speed);
}

.card:hover .task-icon {
    transform: scale(1.1);
}

/* Button Styles */
.btn {
    border-radius: 8px;
    transition: all var(--transition-speed);
    font-weight: 500;
    border: none;
    padding: 0.625rem;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    color: #2d3436;
    background: #f8f9fa;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-outline-success {
    background: rgba(40, 167, 69, 0.1);
    color: var(--success-color);
}

.btn-outline-success:hover {
    background: var(--success-color);
    color: white;
}

.btn-outline-warning {
    background: rgba(255, 193, 7, 0.1);
    color: #b88a00;
}

.btn-outline-warning:hover {
    background: var(--warning-color);
    color: #2d3436;
}

.btn-outline-danger {
    background: rgba(220, 53, 69, 0.1);
    color: var(--danger-color);
}

.btn-outline-danger:hover {
    background: var(--danger-color);
    color: white;
}

/* Status Message Styles */
.status-message {
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 10px;
    background-color: rgba(0, 0, 0, 0.03);
}

/* Section Headers */
.section-header {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-header i {
    font-size: 1.25rem;
}

/* Task Actions */
.task-actions {
    display: flex;
    gap: 1rem;
}

.action-btn {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    cursor: pointer;
    background: transparent;
    font-size: 1.2rem;
    padding: 0.5rem;
    min-width: 48px;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.done-btn {
    color: #28a745;
    border-color: rgba(40, 167, 69, 0.3);
}

.done-btn:hover {
    background-color: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.snooze-btn {
    display: none;
    color: #ffc107;
    border-color: rgba(255, 193, 7, 0.3);
}

.snooze-btn:hover {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.cancel-btn {
    color: #dc3545;
    border-color: rgba(220, 53, 69, 0.3);
}

.cancel-btn:hover {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.task-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.task-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.task-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.task-content h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 500;
}

.task-meta {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 4px;
}

/* Enhanced Greeting Styles */
.greeting-section {
    background: white;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.greeting-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
}

.greeting-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.greeting-icon {
    width: 3rem;
    height: 3rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(205, 175, 86, 0.1);
    transition: all 0.2s ease;
}

.greeting-icon i {
    font-size: 1.5rem;
    color: var(--primary-color);
    text-decoration: none;
}

.greeting-text {
    font-size: 1.25rem;
    color: #2d3436;
    font-weight: 500;
}

.greeting-actions {
    display: flex;
    align-items: center;
}

.settings-btn {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(205, 175, 86, 0.1);
    color: var(--primary-color);
    text-decoration: none;
    transition: all 0.2s ease;
}

.settings-btn:hover {
    background: var(--primary-color);
    color: white;
    transform: rotate(45deg);
}

/* Responsive Styles */
@media (min-width: 992px) {
    .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 2rem;
    }
    
    .greeting-section {
        padding: 1rem 0;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .task-icon {
        width: 56px;
        height: 56px;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
    }
    
    .row {
        margin-left: -1rem;
        margin-right: -1rem;
    }
    
    .col-lg-6 {
        padding-left: 1rem;
        padding-right: 1rem;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding: 0 1rem;
    }
    
    .greeting-section {
        padding: 1rem;
        margin: 0 0 1rem 0;
        border-radius: 0;
        background: linear-gradient(to bottom, white, #f8f9fa);
    }

    .greeting-left {
        gap: 0.875rem;
    }

    .greeting-icon {
        width: 2.5rem;
        height: 2.5rem;
    }

    .greeting-icon i {
        font-size: 1.25rem;
    }

    .greeting-text {
        font-size: 1.125rem;
        line-height: 1.4;
    }

    .settings-btn {
        width: 2.5rem;
        height: 2.5rem;
        font-size: 1rem;
    }

    .task-card {
        padding: 0.875rem;
        border-radius: 14px;
        flex-direction: column;
        align-items: stretch;
    }

    .task-left {
        width: 100%;
        position: relative;
        padding-left: 52px;
        min-height: 42px;
        margin-bottom: 4px;
    }

    .task-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: 10px;
        position: absolute;
        left: 0;
        top: 0;
    }

    .task-content {
        width: 100%;
        padding-right: 4px;
    }

    .task-content h3 {
        font-size: 0.9375rem;
        margin-bottom: 0.25rem;
        line-height: 1.3;
        white-space: normal;
        word-wrap: break-word;
    }

    .task-meta {
        font-size: 0.8125rem;
        white-space: normal;
        word-wrap: break-word;
    }

    .task-actions {
        width: 100%;
        justify-content: center;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid #f1f3f5;
        gap: 0.75rem;
    }

    .action-btn {
        width: 84px;
        height: 44px;
        min-width: 84px;
        border-radius: 8px;
        font-size: 1.1rem;
        padding: 0.4rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .action-btn i {
        font-size: 1.1rem;
    }

    .action-btn::after {
        content: attr(title);
        font-size: 0.875rem;
        font-weight: 500;
    }

    .done-btn::after {
        content: "Done";
    }

    .snooze-btn::after {
        content: "Snooze";
    }

    .cancel-btn::after {
        content: "Cancel";
    }
}

/* Animation Keyframes */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.card {
    animation: fadeIn 0.3s ease-out;
}

.task-card {
    background: white;
    border-radius: 16px;
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.75rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.task-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}

.task-content {
    flex: 1;
    min-width: 0;
}

.task-content h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #2d3436;
    white-space: normal;
    word-wrap: break-word;
    line-height: 1.3;
}

.task-meta {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #6c757d;
    white-space: normal;
    word-wrap: break-word;
}

.task-meta time {
    font-weight: 600;
}

.task-actions {
    display: flex;
    gap: 0.5rem;
}

/* Snooze Modal Styles */
.modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.modal-header {
    border-bottom: 1px solid #f1f3f5;
    padding: 1.25rem 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

.modal-title {
    font-weight: 600;
    color: #2d3436;
}

#snoozeTaskInfo {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 12px;
    color: #2d3436;
}

#snoozeOptions .btn {
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border-radius: 10px;
    transition: all 0.2s ease;
}

#snoozeOptions .btn:hover {
    transform: translateX(5px);
}

#editTaskOption .btn {
    padding: 0.75rem 2rem;
    border-radius: 10px;
}

.action-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: rgba(0, 0, 0, 0.05) !important;
    color: #6c757d !important;
}

.action-btn.disabled:hover {
    transform: none;
    background-color: rgba(0, 0, 0, 0.05) !important;
    color: #6c757d !important;
}

.task-status {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 1rem;
    background-color: rgba(0, 0, 0, 0.05);
    color: #6c757d;
    margin-left: 0.5rem;
}

.task-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

/* Add this to the existing style section */
.date-navigation {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin: 1rem auto;
    background: transparent;
    box-shadow: none;
    padding: 0;
}

.date-display {
    font-weight: 500;
    color: #2d3436;
    text-align: center;
    font-size: 1.1rem;
}

.date-navigation .btn {
    width: 40px;
    height: 40px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 1px solid #e0e0e0;
    color: #2d3436;
    border-radius: 8px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.date-navigation .btn:hover {
    background: #f8f9fa;
}

@media (max-width: 576px) {
    .date-navigation {
        margin: 0.5rem auto;
    }
    
    .date-display {
        font-size: 1rem;
    }
    
    .date-navigation .btn {
        width: 36px;
        height: 36px;
    }
}
</style>

<script>
let currentTaskId = null;
let snoozeModal = null;

document.addEventListener('DOMContentLoaded', function() {
    snoozeModal = new bootstrap.Modal(document.getElementById('snoozeModal'));
    
    // Set default due date to today
    document.getElementById('due_date').valueAsDate = new Date();
});

function handleTaskAction(taskId, action) {
    if (action === 'snooze') {
        showSnoozeOptions(taskId);
    } else {
        let confirmMessage = '';
        switch (action) {
            case 'done':
                confirmMessage = 'Mark this task as completed?';
                break;
            case 'cancel':
                confirmMessage = 'Cancel this task?';
                break;
        }
        
        if (confirm(confirmMessage)) {
            // Show loading state
            const taskCard = document.querySelector(`.task-card button[onclick*="${taskId}"]`).closest('.task-card');
            taskCard.style.opacity = '0.7';
            taskCard.style.pointerEvents = 'none';
            
            // Send request to update task status
            fetch('task_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_task_status&task_id=${taskId}&status=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fade out and remove the task card
                    taskCard.style.transition = 'all 0.3s ease';
                    taskCard.style.opacity = '0';
                    taskCard.style.transform = 'translateX(20px)';
                    setTimeout(() => {
                        if (action === 'snooze') {
                            // For snooze, reload the page to show updated time
                            window.location.reload();
                        } else {
                            // For done/cancel, remove the task
                            taskCard.remove();
                        }
                    }, 300);
                } else {
                    // Show error and reset the card
                    alert('Error: ' + data.message);
                    taskCard.style.opacity = '1';
                    taskCard.style.pointerEvents = 'auto';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the task.');
                taskCard.style.opacity = '1';
                taskCard.style.pointerEvents = 'auto';
            });
        }
    }
}

function showSnoozeOptions(taskId) {
    currentTaskId = taskId;
    
    // Get task details
    fetch(`task_actions.php?action=get_task&task_id=${taskId}`)
        .then(response => response.json())
        .then(task => {
            const taskDueDate = new Date(task.due_date + ' ' + task.due_time);
            const now = new Date();
            const taskInfo = document.getElementById('snoozeTaskInfo');
            const snoozeOptions = document.getElementById('snoozeOptions');
            const editOption = document.getElementById('editTaskOption');
            
            if (taskDueDate > now) {
                // Due date hasn't arrived yet
                taskInfo.innerHTML = `
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        The due date for this task is <strong>${task.due_date} ${task.due_time}</strong>
                        <br>You can edit the task details if needed.
                    </div>`;
                snoozeOptions.style.display = 'none';
                editOption.style.display = 'block';
            } else {
                // Task is due or overdue
                taskInfo.innerHTML = `
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="task-icon">
                            <i class="${task.category_icon}"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">${task.title}</h6>
                            <small class="text-muted">Due: ${task.due_date} ${task.due_time}</small>
                        </div>
                    </div>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Choose how long to snooze this task:
                    </div>`;
                snoozeOptions.style.display = 'flex';
                editOption.style.display = 'none';
            }
            
            snoozeModal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading task details');
        });
}

function handleSnoozeSelection(minutes) {
    if (!currentTaskId) return;
    
    const taskCard = document.querySelector(`.task-card button[onclick*="${currentTaskId}"]`).closest('.task-card');
    taskCard.style.opacity = '0.7';
    taskCard.style.pointerEvents = 'none';
    
    fetch('task_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=snooze_task&task_id=${currentTaskId}&minutes=${minutes}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            snoozeModal.hide();
            taskCard.style.transition = 'all 0.3s ease';
            taskCard.style.opacity = '0';
            taskCard.style.transform = 'translateX(20px)';
            setTimeout(() => {
                window.location.reload();
            }, 300);
        } else {
            alert('Error: ' + data.message);
            taskCard.style.opacity = '1';
            taskCard.style.pointerEvents = 'auto';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while snoozing the task.');
        taskCard.style.opacity = '1';
        taskCard.style.pointerEvents = 'auto';
    });
}

function showFutureTaskMessage(taskId) {
    // Get task details
    fetch(`task_actions.php?action=get_task&task_id=${taskId}`)
        .then(response => response.json())
        .then(task => {
            const dueDateTime = new Date(task.due_date + ' ' + task.due_time);
            const formattedDate = dueDateTime.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: 'numeric'
            });
            
            const taskCard = document.querySelector(`.task-card button[onclick*="${taskId}"]`).closest('.task-card');
            const existingMessage = taskCard.querySelector('.future-task-message');
            
            if (!existingMessage) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'alert alert-info mt-2 mb-0 future-task-message';
                messageDiv.style.fontSize = '0.875rem';
                messageDiv.innerHTML = `
                    <i class="fas fa-info-circle me-2"></i>
                    This task is scheduled for <strong>${formattedDate}</strong>. You can't snooze it yet.
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-primary" onclick="openEditTask(${taskId})">
                            <i class="fas fa-edit me-1"></i> Edit Task
                        </button>
                    </div>
                `;
                
                // Insert after task content
                const taskContent = taskCard.querySelector('.task-content');
                taskContent.parentNode.insertBefore(messageDiv, taskContent.nextSibling);
                
                // Remove message after 5 seconds
                setTimeout(() => {
                    messageDiv.remove();
                }, 5000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Update the openEditTask function to work with the message
function openEditTask(taskId) {
    window.location.href = `manage_tasks.php?task_id=${taskId}`;
}

document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission
    document.getElementById('addTaskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('save_task.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const alertContainer = document.getElementById('alert-container');
                alertContainer.innerHTML = `
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addTaskModal'));
                modal.hide();
                
                // Reload page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                // Show error message
                const alertContainer = document.getElementById('alert-container');
                alertContainer.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const alertContainer = document.getElementById('alert-container');
            alertContainer.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    An error occurred while adding the task.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
        });
    });
});

// Add this new function to check and show snooze buttons
function checkAndShowSnoozeButtons() {
    const now = new Date();
    document.querySelectorAll('.snooze-btn').forEach(button => {
        const taskDueDate = button.dataset.dueDate;
        const taskDueTime = button.dataset.dueTime;
        const dueDateTime = new Date(taskDueDate + ' ' + taskDueTime);
        
        if (dueDateTime <= now) {
            button.style.display = 'flex';
            // Add a subtle animation when showing the button
            button.style.animation = 'fadeIn 0.3s ease-out';
        }
    });
}

// Check every minute for tasks that need snooze button shown
setInterval(checkAndShowSnoozeButtons, 60000);

// Also check when the page loads
document.addEventListener('DOMContentLoaded', function() {
    checkAndShowSnoozeButtons();
    // ... rest of the existing DOMContentLoaded code ...
});
</script>

<?php require_once '../../includes/footer.php'; ?> 