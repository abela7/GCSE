<?php
// GCSE/pages/tasks/manage_tasks.php
// Complete Code - Updated - No Null Coalescing Operator (??)

// Set timezone to London
date_default_timezone_set('Europe/London');

// --- Core Includes & Setup ---
session_start();
require_once __DIR__ . '/../../config/db_connect.php';
//require_once __DIR__ . '/../../includes/functions.php'; // If needed
require_once __DIR__ . '/task_functions.php';

// --- Instance Generation ---
try {
    if (!isset($_SESSION['instances_generated_today']) || $_SESSION['instances_generated_today'] !== date('Y-m-d')) {
         if ($conn->begin_transaction()) {
             generateTaskInstances($conn);
             $conn->commit();
             $_SESSION['instances_generated_today'] = date('Y-m-d');
             error_log("Task instances generated successfully for " . date('Y-m-d'));
         } else {
              throw new Exception("Failed to start transaction for instance generation.");
         }
    }
} catch (Exception $e) {
    if ($conn->ping() && $conn->inTransaction) { $conn->rollback(); }
    error_log("Error generating task instances on manage_tasks load: " . $e->getMessage());
}

// --- Action Handling ---
$task_to_edit = null;

// POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $redirect_date_str = date('Y-m-d'); // Default
    if (!empty($_POST['current_view_date'])) { $redirect_date_str = $_POST['current_view_date'];
    } elseif (isset($_SERVER['HTTP_REFERER'])) {
        $referer_query = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
        if ($referer_query) { parse_str($referer_query, $referer_params);
            if (isset($referer_params['date'])) { $redirect_date_str = $referer_params['date']; }
        }
    }
    $redirect_url = 'manage_tasks.php?date=' . urlencode($redirect_date_str);
    $result = null;

    try {
        if ($conn->begin_transaction()) {
             switch ($_POST['action']) {
                case 'save_task':
                    error_log("Attempting save_task with POST data: " . print_r($_POST, true));
                    $result = saveTask($conn, $_POST);
                    break;
                case 'update_task':
                    if (!isset($_POST['task_id'])) throw new Exception("Task ID missing for update.");
                    error_log("Attempting update_task for ID {$_POST['task_id']} with POST data: " . print_r($_POST, true));
                    $result = updateTask($conn, $_POST);
                    break;
                case 'update_status':
                    if (!isset($_POST['task_id']) || !isset($_POST['status'])) throw new Exception("Required parameters missing for status update.");
                    $instance_date = isset($_POST['due_date']) && !empty($_POST['due_date']) ? $_POST['due_date'] : null;
                    $result = updateTaskStatus($conn, (int)$_POST['task_id'], $_POST['status'], $instance_date);
                    break;
                case 'snooze_task':
                    if (!isset($_POST['task_id'])) throw new Exception("Task ID missing for snooze.");
                    $instance_date = isset($_POST['due_date']) && !empty($_POST['due_date']) ? $_POST['due_date'] : null;
                    $result = snoozeTask($conn, (int)$_POST['task_id'], $instance_date);
                    break;
                default: throw new Exception('Invalid POST action specified.');
            }
            if (isset($result) && is_array($result) && array_key_exists('success', $result)) {
                 if ($result['success']) { $conn->commit(); $_SESSION['success'] = isset($result['message']) ? $result['message'] : 'Action completed successfully.'; }
                 else { throw new Exception(isset($result['message']) ? $result['message'] : 'An unknown error occurred during the action.'); }
            } else { throw new Exception('Action did not produce a valid result format.'); }
        } else { throw new Exception("Failed to start database transaction."); }
    } catch (Exception $e) {
        if ($conn->ping() && $conn->inTransaction) { $conn->rollback(); }
        $_SESSION['error'] = "Error processing '{$_POST['action']}': " . $e->getMessage();
        error_log("Task Action POST Error: Action='{$_POST['action']}' Error=" . $e->getMessage());
    }
    header("Location: " . $redirect_url); exit;
}

// GET Actions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $redirect_date_str = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $redirect_url = 'manage_tasks.php?date=' . urlencode($redirect_date_str);
    $needs_redirect = true; $use_transaction = false; // Initialize

    try {
        $action = $_GET['action'];
        if (in_array($action, ['delete_task', 'toggle_active', 'edit_task'])) {
            if (!isset($_GET['id'])) { throw new Exception("Task ID missing for action '$action'."); }
            $task_id = (int)$_GET['id'];
            if ($task_id <= 0) { throw new Exception("Invalid Task ID for action '$action'."); }
            $result = null; $use_transaction = in_array($action, ['delete_task', 'toggle_active']);
            if ($use_transaction && !$conn->begin_transaction()) { throw new Exception("Failed to start transaction for GET action '$action'."); }
            switch ($action) {
                case 'delete_task': $result = deleteTask($conn, $task_id); break;
                case 'toggle_active': $activate = isset($_GET['activate']) && $_GET['activate'] == '1'; $result = toggleTaskStatus($conn, $task_id, $activate); break;
                case 'edit_task': $needs_redirect = false; $edit_result = getTask($conn, $task_id);
                    if (isset($edit_result['success']) && $edit_result['success']) { $task_to_edit = $edit_result['task']; }
                    else { $_SESSION['error'] = isset($edit_result['message']) ? $edit_result['message'] : 'Could not fetch task for editing.'; header("Location: " . $redirect_url); exit; }
                    break;
            }
            if ($use_transaction) {
                 if (isset($result) && is_array($result) && array_key_exists('success', $result)) {
                      if ($result['success']) { $conn->commit(); $_SESSION['success'] = isset($result['message']) ? $result['message'] : 'Action completed.'; }
                      else { $conn->rollback(); $_SESSION['error'] = isset($result['message']) ? $result['message'] : 'An error occurred.'; }
                 } else { $conn->rollback(); $_SESSION['error'] = 'Action did not complete.'; }
            } elseif (isset($result) && is_array($result) && array_key_exists('success', $result) && !$result['success']) { $_SESSION['error'] = isset($result['message']) ? $result['message'] : 'An error occurred.'; }
        } else { throw new Exception("Invalid or unsupported GET action: '$action'."); }
    } catch (Exception $e) {
        if ($conn->ping() && $conn->inTransaction && $use_transaction) { $conn->rollback(); }
        $_SESSION['error'] = "Error: " . $e->getMessage();
        error_log("Task GET Action Error: Action='{$_GET['action']}' ID='" . (isset($_GET['id']) ? $_GET['id'] : 'N/A') . "' Error=" . $e->getMessage());
        $needs_redirect = true;
    }
    if ($needs_redirect) { header("Location: " . $redirect_url); exit; }
}

// --- Data Fetching for Display ---
$selected_date_str = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
try { $selected_date = new DateTimeImmutable($selected_date_str); }
catch (Exception $e) { $selected_date = new DateTimeImmutable();
    if(isset($_GET['date'])) { $_SESSION['warning'] = "Invalid date format. Showing today's tasks."; }
}
$formatted_selected_date = $selected_date->format('Y-m-d');
$display_date_str = $selected_date->format('l, F j, Y');
$prev_date = $selected_date->modify('-1 day')->format('Y-m-d');
$next_date = $selected_date->modify('+1 day')->format('Y-m-d');

// Fetch tasks using UNION ALL - Including all task statuses
$sql = "SELECT 
    t.id AS task_id, 
    t.title, 
    t.description, 
    t.priority, 
    t.task_type, 
    t.category_id, 
    t.is_active AS task_is_active, 
    t.estimated_duration, 
    c.name AS category_name, 
    c.icon AS category_icon, 
    c.color AS category_color, 
    NULL AS instance_id, 
    t.due_date AS effective_due_date, 
    t.due_time AS effective_due_time, 
    t.status AS effective_status 
FROM tasks t 
JOIN task_categories c ON t.category_id = c.id 
WHERE t.task_type = 'one-time' 
AND t.is_active = 1 
AND t.due_date = ?

UNION ALL

SELECT 
    t.id AS task_id, 
    t.title, 
    t.description, 
    t.priority, 
    t.task_type, 
    t.category_id, 
    t.is_active AS task_is_active, 
    t.estimated_duration, 
    c.name AS category_name, 
    c.icon AS category_icon, 
    c.color AS category_color, 
    ti.id AS instance_id, 
    ti.due_date AS effective_due_date, 
    ti.due_time AS effective_due_time, 
    ti.status AS effective_status 
FROM task_instances ti 
JOIN tasks t ON ti.task_id = t.id 
JOIN task_categories c ON t.category_id = c.id 
WHERE t.is_active = 1 
AND ti.due_date = ?

ORDER BY 
    FIELD(effective_status, 'pending', 'snoozed', 'completed', 'not_done'), 
    CASE WHEN effective_due_time IS NULL THEN 1 ELSE 0 END, 
    effective_due_time ASC, 
    FIELD(priority, 'high', 'medium', 'low'), 
    title ASC";
$stmt = $conn->prepare($sql);
$morning_tasks = []; $evening_tasks = [];
if (!$stmt) { error_log("Prepare failed (UNION query): " . $conn->error); $_SESSION['error'] = "Error fetching tasks.";
} else {
    $stmt->bind_param("ss", $formatted_selected_date, $formatted_selected_date);
    if (!$stmt->execute()) { error_log("Execute failed (UNION query): " . $stmt->error); $_SESSION['error'] = "Error executing task fetch.";
    } else {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
             $task_data = [
                'id' => $row['task_id'], 'instance_id' => isset($row['instance_id']) ? $row['instance_id'] : null,
                'title' => isset($row['title']) ? $row['title'] : '', 'description' => isset($row['description']) ? $row['description'] : null,
                'priority' => isset($row['priority']) ? $row['priority'] : 'medium', 'task_type' => isset($row['task_type']) ? $row['task_type'] : 'one-time',
                'category_id' => isset($row['category_id']) ? (int)$row['category_id'] : null, 'category_name' => isset($row['category_name']) ? $row['category_name'] : 'Uncategorized',
                'category_icon' => isset($row['category_icon']) ? $row['category_icon'] : 'fas fa-tasks', 'category_color' => isset($row['category_color']) ? $row['category_color'] : '#6c757d',
                'is_active' => isset($row['task_is_active']) ? (int)$row['task_is_active'] : 1, 'due_date' => isset($row['effective_due_date']) ? $row['effective_due_date'] : null,
                'due_time' => isset($row['effective_due_time']) ? $row['effective_due_time'] : null, 'status' => isset($row['effective_status']) ? $row['effective_status'] : 'pending',
                'estimated_duration' => isset($row['estimated_duration']) ? (int)$row['estimated_duration'] : 0 ];
            if (isset($task_data['due_time']) && strtotime($task_data['due_time']) < strtotime('12:00:00')) { $morning_tasks[] = $task_data; }
            else { $evening_tasks[] = $task_data; }
        } $result->free();
    } $stmt->close();
}
// Fetch active categories
$categories = []; $categories_result = $conn->query("SELECT id, name FROM task_categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC");
if ($categories_result) { while ($cat = $categories_result->fetch_assoc()) { $categories[$cat['id']] = $cat; } $categories_result->free();
} else { error_log("Error fetching categories: " . $conn->error); $_SESSION['error'] = (isset($_SESSION['error']) ? $_SESSION['error'] : '') . ' Could not load task categories.'; }

// --- Start HTML Output ---
$page_title = "Tasks - " . $selected_date->format('M d, Y');
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
    /* Modal Scrolling Fixes */
    .modal-dialog-scrollable .modal-content {
        max-height: 90vh;
    }
    
    .modal-dialog-scrollable .modal-body {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Ensure modal header and footer stay fixed */
    .modal-header, .modal-footer {
        flex-shrink: 0;
    }
    
    /* Prevent content from being cut off */
    .modal-body {
        padding-right: 1rem;
    }
    
    /* Custom scrollbar for better visibility */
    .modal-body::-webkit-scrollbar {
        width: 8px;
    }
    
    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .modal-body::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    
    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    
    /* Task Status Styling */
    .status-completed {
        border-left: 4px solid var(--bs-success);
        background-color: rgba(25, 135, 84, 0.05);
    }
    
    .status-not_done {
        border-left: 4px solid var(--bs-danger);
        background-color: rgba(220, 53, 69, 0.05);
    }
    
    .status-snoozed {
        border-left: 4px solid var(--bs-warning);
        background-color: rgba(255, 193, 7, 0.05);
    }
    
    .status-pending {
        border-left: 4px solid var(--bs-secondary);
    }
    
    /* Status badge styling */
    .task-status-badge {
        font-size: 0.75rem;
        padding: 0.25em 0.6em;
        border-radius: 50rem;
    }
    
    /* Quick filter button styling */
    .btn-status-filter {
        transition: all 0.2s ease;
    }
    
    .btn-status-filter.active {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    /* Task summary circles */
    .task-stat-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        transition: all 0.3s ease;
    }
    
    .task-stat-circle:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="container-fluid my-3 my-md-4">
    <!-- Session Messages -->
    <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php unset($_SESSION['success']); endif; ?>
    <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php unset($_SESSION['error']); endif; ?>
    <?php if (!empty($_SESSION['warning'])): ?><div class="alert alert-warning alert-dismissible fade show" role="alert"><i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['warning']); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php unset($_SESSION['warning']); endif; ?>

    <!-- Header Area -->
    <div class="page-header-controls sticky-top shadow-sm"> <div class="d-flex justify-content-between align-items-center flex-column flex-md-row"> <div class="date-nav d-flex align-items-center me-md-3 mb-3 mb-md-0 order-2 order-md-1"> <a href="?date=<?php echo $prev_date; ?>" class="btn btn-outline-secondary" aria-label="Previous Day"><i class="fas fa-chevron-left"></i></a> <span class="current-date mx-3"><?php echo $display_date_str; ?></span> <a href="?date=<?php echo $next_date; ?>" class="btn btn-outline-secondary" aria-label="Next Day"><i class="fas fa-chevron-right"></i></a> </div> <div class="action-buttons d-flex align-items-center gap-2 order-1 order-md-2 mb-3 mb-md-0"> <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="prepareAddTaskModal()"><i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Add</span></button> <a href="categories.php" class="btn btn-outline-secondary" title="Manage Categories"><i class="fas fa-folder"></i></a> <a href="task_list.php" class="btn btn-outline-secondary" title="View All Tasks"><i class="fas fa-list"></i></a> </div> </div> </div>

    <!-- Quick Filters for Task Status -->
    <div class="quick-filters mb-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2 text-muted"></i>Quick View</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="?date=<?php echo $formatted_selected_date; ?>" class="btn btn-outline-primary btn-status-filter">
                        <i class="fas fa-tasks me-1"></i> All Tasks
                    </a>
                    <a href="?date=<?php echo $formatted_selected_date; ?>&status=pending" class="btn btn-outline-secondary btn-status-filter">
                        <i class="fas fa-hourglass-half me-1"></i> Pending
                    </a>
                    <a href="?date=<?php echo $formatted_selected_date; ?>&status=completed" class="btn btn-outline-success btn-status-filter">
                        <i class="fas fa-check-circle me-1"></i> Completed
                    </a>
                    <a href="?date=<?php echo $formatted_selected_date; ?>&status=not_done" class="btn btn-outline-danger btn-status-filter">
                        <i class="fas fa-times-circle me-1"></i> Not Done
                    </a>
                    <a href="?date=<?php echo $formatted_selected_date; ?>&status=snoozed" class="btn btn-outline-warning btn-status-filter">
                        <i class="fas fa-clock me-1"></i> Snoozed
                    </a>
                </div>
                <div class="mt-3 text-center">
                    <span class="text-muted small">Filter by task status or use the <a href="#" data-bs-toggle="collapse" data-bs-target="#filterSection">advanced filters</a> below</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Filter Section -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-filter me-2 text-muted"></i>Task Filters</h5>
                <button class="btn btn-sm btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#filterSection" aria-expanded="false">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
        <div class="collapse" id="filterSection">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="filterCategory" class="form-label small">Category</label>
                        <select class="form-select form-select-sm" id="filterCategory">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $id => $category): ?>
                                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filterStatus" class="form-label small">Status</label>
                        <select class="form-select form-select-sm" id="filterStatus">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="snoozed">Snoozed</option>
                            <option value="not_done">Not Done</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filterPriority" class="form-label small">Priority</label>
                        <select class="form-select form-select-sm" id="filterPriority">
                            <option value="">All Priorities</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filterDuration" class="form-label small">Duration</label>
                        <select class="form-select form-select-sm" id="filterDuration">
                            <option value="">Any Duration</option>
                            <option value="short">Short (< 15 min)</option>
                            <option value="medium">Medium (15-30 min)</option>
                            <option value="long">Long (> 30 min)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filterType" class="form-label small">Task Type</label>
                        <select class="form-select form-select-sm" id="filterType">
                            <option value="">All Types</option>
                            <option value="one-time">One-time</option>
                            <option value="recurring">Recurring</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="filterTimePeriod" class="form-label small">Time Period</label>
                        <select class="form-select form-select-sm" id="filterTimePeriod">
                            <option value="">All Times</option>
                            <option value="morning">Morning</option>
                            <option value="evening">Evening</option>
                            <option value="no_time">No Time Set</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="button" id="resetFilters" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                    <button type="button" id="applyFilters" class="btn btn-sm btn-primary">
                        <i class="fas fa-check me-1"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Summary -->
    <div class="row mt-4 mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-chart-pie me-2 text-primary"></i>Task Summary</h5>
                    <div class="d-flex flex-wrap justify-content-around gap-3">
                        <?php 
                            // Count tasks by status
                            $pending_count = 0;
                            $completed_count = 0;
                            $not_done_count = 0;
                            $snoozed_count = 0;
                            
                            foreach (array_merge($morning_tasks, $evening_tasks) as $task) {
                                $status = isset($task['status']) ? $task['status'] : 'pending';
                                if ($status === 'pending') $pending_count++;
                                elseif ($status === 'completed') $completed_count++;
                                elseif ($status === 'not_done') $not_done_count++;
                                elseif ($status === 'snoozed') $snoozed_count++;
                            }
                            
                            $total_count = count($morning_tasks) + count($evening_tasks);
                        ?>
                        
                        <div class="text-center">
                            <div class="d-flex align-items-center justify-content-center bg-light rounded-circle" style="width: 50px; height: 50px; margin: 0 auto;">
                                <h3 class="mb-0 text-primary"><?php echo $total_count; ?></h3>
                            </div>
                            <div class="mt-2">Total</div>
                        </div>
                        
                        <div class="text-center">
                            <div class="d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 rounded-circle" style="width: 50px; height: 50px; margin: 0 auto;">
                                <h3 class="mb-0 text-secondary"><?php echo $pending_count; ?></h3>
                            </div>
                            <div class="mt-2">Pending</div>
                        </div>
                        
                        <div class="text-center">
                            <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 rounded-circle" style="width: 50px; height: 50px; margin: 0 auto;">
                                <h3 class="mb-0 text-success"><?php echo $completed_count; ?></h3>
                            </div>
                            <div class="mt-2">Completed</div>
                        </div>
                        
                        <div class="text-center">
                            <div class="d-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle" style="width: 50px; height: 50px; margin: 0 auto;">
                                <h3 class="mb-0 text-danger"><?php echo $not_done_count; ?></h3>
                            </div>
                            <div class="mt-2">Not Done</div>
                        </div>
                        
                        <div class="text-center">
                            <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 rounded-circle" style="width: 50px; height: 50px; margin: 0 auto;">
                                <h3 class="mb-0 text-warning"><?php echo $snoozed_count; ?></h3>
                            </div>
                            <div class="mt-2">Snoozed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks Area -->
    <div class="row mt-4"> 
        <div class="col-lg-6 mb-4" id="morning-tasks"> 
            <h2 class="section-heading"><i class="fas fa-sun text-warning"></i> Morning Tasks</h2> 
            <?php if (empty($morning_tasks)): ?> 
                <div class="text-center text-muted p-3 bg-light" style="border: 1px dashed var(--border-color); border-radius: var(--border-radius-md);">No morning tasks scheduled.</div> 
            <?php else: ?> 
                <div class="tasks-list d-flex flex-column gap-3"> 
                    <?php foreach ($morning_tasks as $task): ?> 
                        <?php include '_task_card.php'; ?> 
                    <?php endforeach; ?> 
                </div> 
            <?php endif; ?> 
        </div> 
        <div class="col-lg-6 mb-4" id="evening-tasks"> 
            <h2 class="section-heading"><i class="fas fa-moon text-primary"></i> Evening Tasks</h2> 
            <?php if (empty($evening_tasks)): ?> 
                <div class="text-center text-muted p-3 bg-light" style="border: 1px dashed var(--border-color); border-radius: var(--border-radius-md);">No evening tasks scheduled.</div> 
            <?php else: ?> 
                <div class="tasks-list d-flex flex-column gap-3"> 
                    <?php foreach ($evening_tasks as $task): ?> 
                        <?php include '_task_card.php'; ?> 
                    <?php endforeach; ?> 
                </div> 
            <?php endif; ?> 
        </div> 
    </div>
</div>

<!-- Add/Edit Task Modal -->
<div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
           <form id="taskForm" method="post" action="manage_tasks.php" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="taskModalLabel">Add Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <input type="hidden" name="action" id="form_action" value="save_task">
                    <input type="hidden" name="task_id" id="task_id">
                    <input type="hidden" name="current_view_date" value="<?php echo htmlspecialchars($formatted_selected_date); ?>">

                    <div class="mb-3">
                        <label for="task_title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="task_title" name="title" required>
                        <div class="invalid-feedback">Please enter a task title.</div>
                    </div>

                    <div class="mb-3">
                        <label for="task_description" class="form-label">Description</label>
                        <textarea class="form-control" id="task_description" name="description" rows="3"></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="task_category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="task_category_id" name="category_id" required>
                                <option value="" disabled selected>Select...</option>
                                <?php foreach ($categories as $id => $category): ?>
                                    <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                                <?php if (empty($categories)): ?>
                                    <option value="" disabled>No categories found</option>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback">Please select a category.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="task_priority" class="form-label">Priority <span class="text-danger">*</span></label>
                            <select class="form-select" id="task_priority" name="priority" required>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                                <option value="high">High</option>
                            </select>
                            <div class="invalid-feedback">Please select priority.</div>
                        </div>
                    </div>

                    <div class="my-3">
                        <label for="task_status" class="form-label">Status</label>
                        <select class="form-select" id="task_status" name="status">
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="snoozed">Snoozed</option>
                            <option value="not_done">Not Done</option>
                        </select>
                        <small class="text-muted">Sets status for one-time tasks or default for new recurring.</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="task_due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="task_due_date" name="due_date" required value="<?php echo htmlspecialchars($formatted_selected_date); ?>">
                            <div class="invalid-feedback">Please select due date.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="task_due_time" class="form-label">Due Time</label>
                            <input type="time" class="form-control" id="task_due_time" name="due_time" step="300">
                            <small class="text-muted">Optional.</small>
                        </div>
                    </div>

                    <div class="my-3">
                        <label for="task_estimated_duration" class="form-label">Est. Duration (min)</label>
                        <input type="number" class="form-control" id="task_estimated_duration" name="estimated_duration" min="0" step="5">
                    </div>

                    <div class="mb-3">
                        <label for="task_type" class="form-label">Task Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="task_type" name="task_type" onchange="toggleRecurrenceFields()" required>
                            <option value="one-time">One Time</option>
                            <option value="recurring">Recurring</option>
                        </select>
                        <div class="invalid-feedback">Please select task type.</div>
                    </div>

                    <div id="recurrence_fields" style="display: none; border: 1px solid var(--border-color); padding: 15px; border-radius: var(--border-radius-md); margin-bottom: 1rem; background-color: var(--bg-light-gray);">
                        <h6 class="mb-3 text-muted">Recurrence Details</h6>
                        <div class="mb-3">
                            <label for="task_frequency" class="form-label">Frequency <span class="text-danger">*</span></label>
                            <select class="form-control" id="task_frequency" name="frequency" onchange="toggleWeeklyDays()">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                            <div class="invalid-feedback">Please select frequency.</div>
                        </div>
                        <div id="weekly_days" class="mb-3" style="display: none;">
                            <label class="form-label">Select Days <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap" style="gap: 0.5rem 1rem;">
                                <?php $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; ?>
                                <?php foreach ($daysOfWeek as $index => $dayName): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="weekly_days[]" id="day_<?php echo $index; ?>" value="<?php echo $index; ?>">
                                        <label class="form-check-label" for="day_<?php echo $index; ?>"><?php echo $dayName; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="weekly_days_feedback" class="invalid-feedback" style="display: none;">Please select at least one day.</div>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="task_is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="task_is_active">Task is Active</label>
                        <small class="text-muted d-block">Inactive tasks won't appear or generate instances.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
// --- JavaScript for Modal and Basic Interactions (NO ??) ---

let taskModalInstance = null;
const taskForm = document.getElementById('taskForm');

function getElement(id) { const element = document.getElementById(id); return element; } // Simplified

document.addEventListener('DOMContentLoaded', function() {
    const taskModalElement = getElement('taskModal');
    if (taskModalElement) { taskModalInstance = new bootstrap.Modal(taskModalElement); }
    else { console.error("Modal element #taskModal not found!"); return; }
    const taskTypeSelect = getElement('task_type');
    const frequencySelect = getElement('task_frequency');
    if (taskTypeSelect) { taskTypeSelect.addEventListener('change', toggleRecurrenceFields); }
    if (frequencySelect) { frequencySelect.addEventListener('change', toggleWeeklyDays); }
    if (taskForm) { taskForm.addEventListener('submit', handleFormValidationAndSubmit); }
    else { console.error("Task Form #taskForm not found!"); }
    toggleRecurrenceFields(); // Initialize visibility
    <?php if (!empty($task_to_edit)): ?>
    console.log("Populating modal for edit with data:", <?php echo json_encode($task_to_edit); ?>);
    populateAndShowEditModal(<?php echo json_encode($task_to_edit); ?>);
    <?php endif; ?>
    
    // Initialize task filters
    initializeTaskFilters();
});

function prepareAddTaskModal() {
    const form = getElement('taskForm'); if (!form) return;
    form.reset(); form.classList.remove('was-validated');
    getElement('form_action').value = 'save_task'; getElement('task_id').value = '';
    getElement('taskModalLabel').textContent = 'Add New Task';
    getElement('task_is_active').checked = true; getElement('task_priority').value = 'medium';
    getElement('task_status').value = 'pending'; const taskTypeSelect = getElement('task_type');
    if(taskTypeSelect) taskTypeSelect.value = 'one_time';
    const urlParams = new URLSearchParams(window.location.search);
    const currentDate = urlParams.get('date') || new Date().toISOString().split('T')[0];
    getElement('task_due_date').value = currentDate;
    form.querySelectorAll('input[name="weekly_days[]"]').forEach(checkbox => checkbox.checked = false);
    toggleRecurrenceFields();
}

function populateAndShowEditModal(task) {
     const form = getElement('taskForm'); if (!form || !task || typeof task !== 'object') { console.error("Invalid task data for editing:", task); return; }
     form.reset(); form.classList.remove('was-validated');
    getElement('form_action').value = 'update_task'; getElement('task_id').value = task.id || '';
    getElement('taskModalLabel').textContent = 'Edit Task';
    getElement('task_title').value = task.title || ''; getElement('task_description').value = task.description || '';
    getElement('task_category_id').value = task.category_id || ''; getElement('task_type').value = task.task_type || 'one-time';
    getElement('task_priority').value = task.priority || 'medium'; getElement('task_due_date').value = task.due_date || '';
    getElement('task_due_time').value = task.due_time || ''; getElement('task_estimated_duration').value = task.estimated_duration || '';
    getElement('task_is_active').checked = task.is_active == 1; getElement('task_status').value = task.status || 'pending';
    const taskTypeSelect = getElement('task_type'); const frequencySelect = getElement('task_frequency');
    form.querySelectorAll('input[name="weekly_days[]"]').forEach(checkbox => checkbox.checked = false);
    if (taskTypeSelect.value === 'recurring' && frequencySelect) {
         if (task.frequency) { frequencySelect.value = task.frequency; } else { frequencySelect.value = 'daily'; }
        if (frequencySelect.value === 'weekly' && task.specific_days) {
            try { const days = JSON.parse(task.specific_days); if (Array.isArray(days)) { days.forEach(dayIndex => { const checkbox = getElement('day_' + dayIndex); if (checkbox) checkbox.checked = true; }); } }
            catch (e) { console.error("Could not parse specific_days JSON during edit: ", task.specific_days, e); }
        }
    }
    toggleRecurrenceFields(); if (taskModalInstance) { taskModalInstance.show(); }
}

function toggleRecurrenceFields() {
    const taskTypeSelect = getElement('task_type'); const recurrenceFieldsDiv = getElement('recurrence_fields'); const frequencySelect = getElement('task_frequency');
    if (!taskTypeSelect || !recurrenceFieldsDiv || !frequencySelect) return;
    const isRecurring = taskTypeSelect.value === 'recurring';
    recurrenceFieldsDiv.style.display = isRecurring ? 'block' : 'none'; frequencySelect.required = isRecurring;
    if (isRecurring) { toggleWeeklyDays(); }
    else { const weeklyDaysDiv = getElement('weekly_days'); if (weeklyDaysDiv) weeklyDaysDiv.style.display = 'none';
         document.querySelectorAll('input[name="weekly_days[]"]').forEach(checkbox => { checkbox.checked = false; checkbox.classList.remove('is-invalid'); });
         const weeklyDaysFeedback = getElement('weekly_days_feedback'); if(weeklyDaysFeedback) weeklyDaysFeedback.style.display = 'none';
    }
}

function toggleWeeklyDays() {
    const taskTypeSelect = getElement('task_type'); const frequencySelect = getElement('task_frequency'); const weeklyDaysDiv = getElement('weekly_days'); const weeklyCheckboxes = document.querySelectorAll('input[name="weekly_days[]"]');
    if (!taskTypeSelect || !frequencySelect || !weeklyDaysDiv) return; if (taskTypeSelect.value !== 'recurring') { weeklyDaysDiv.style.display = 'none'; return; }
    const isWeekly = frequencySelect.value === 'weekly'; weeklyDaysDiv.style.display = isWeekly ? 'block' : 'none';
    weeklyCheckboxes.forEach(checkbox => checkbox.classList.remove('is-invalid'));
    const weeklyDaysFeedback = getElement('weekly_days_feedback'); if(weeklyDaysFeedback) weeklyDaysFeedback.style.display = 'none';
}

function handleFormValidationAndSubmit(event) {
    event.preventDefault();
    event.stopPropagation();
    const form = event.target;
    form.classList.remove('was-validated');
    let isValid = form.checkValidity();

    const taskType = getElement('task_type').value;
    const frequency = getElement('task_frequency').value;
    const weeklyDaysCheckboxes = form.querySelectorAll('input[name="weekly_days[]"]');
    const weeklyDaysFeedback = getElement('weekly_days_feedback');
    let weeklyDaysValid = true;

    weeklyDaysCheckboxes.forEach(cb => cb.classList.remove('is-invalid'));
    if(weeklyDaysFeedback) weeklyDaysFeedback.style.display = 'none';

    if (taskType === 'recurring' && frequency === 'weekly') {
        const checkedDaysCount = form.querySelectorAll('input[name="weekly_days[]"]:checked').length;
        if (checkedDaysCount === 0) {
            isValid = false;
            weeklyDaysValid = false;
            if(weeklyDaysFeedback) weeklyDaysFeedback.style.display = 'block';
            weeklyDaysCheckboxes.forEach(cb => cb.classList.add('is-invalid'));
        }
    }

    form.classList.add('was-validated');

    if (isValid) {
        console.log("Form valid, preparing submission...");
        const formData = new FormData(form);

        if (taskType === 'recurring' && frequency === 'weekly') {
            const selectedDays = [];
            form.querySelectorAll('input[name="weekly_days[]"]:checked').forEach(checkbox => {
                selectedDays.push(parseInt(checkbox.value));
            });
            formData.delete('weekly_days[]');
            formData.append('specific_days', JSON.stringify(selectedDays));
        }

        // Create a new form and submit it
        const submitForm = document.createElement('form');
        submitForm.method = 'POST';
        submitForm.action = 'manage_tasks.php'; // Set the correct action URL

        for (const [key, value] of formData.entries()) {
            if (key === 'weekly_days[]') continue;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            submitForm.appendChild(input);
        }

        document.body.appendChild(submitForm);
        submitForm.submit();
    } else {
        console.log("Form invalid.");
        const firstInvalid = form.querySelector('.form-control:invalid, .form-select:invalid, .form-check-input.is-invalid');
        if (firstInvalid) {
            setTimeout(() => firstInvalid.focus(), 50);
        }
    }
}

function confirmAction(event, message) { event.preventDefault(); if (confirm(message)) { window.location.href = event.currentTarget.href; } return false; }

// --- Task Filter Functionality ---
function initializeTaskFilters() {
    // Get filter elements
    const filterCategory = getElement('filterCategory');
    const filterStatus = getElement('filterStatus');
    const filterPriority = getElement('filterPriority');
    const filterDuration = getElement('filterDuration');
    const filterType = getElement('filterType');
    const filterTimePeriod = getElement('filterTimePeriod');
    const resetFiltersBtn = getElement('resetFilters');
    const applyFiltersBtn = getElement('applyFilters');
    
    // Verify elements exist
    if (!filterCategory || !filterStatus || !filterPriority || 
        !filterDuration || !filterType || !filterTimePeriod ||
        !resetFiltersBtn || !applyFiltersBtn) {
        console.error("One or more filter elements not found");
        return;
    }
    
    // Apply filters when button is clicked
    applyFiltersBtn.addEventListener('click', function() {
        applyTaskFilters();
    });
    
    // Reset filters when button is clicked
    resetFiltersBtn.addEventListener('click', function() {
        filterCategory.value = '';
        filterStatus.value = '';
        filterPriority.value = '';
        filterDuration.value = '';
        filterType.value = '';
        filterTimePeriod.value = '';
        
        // Show all tasks
        resetTaskVisibility();
    });
    
    // Preselect filters if they're in the URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('category')) filterCategory.value = urlParams.get('category');
    if (urlParams.has('status')) filterStatus.value = urlParams.get('status');
    if (urlParams.has('priority')) filterPriority.value = urlParams.get('priority');
    if (urlParams.has('duration')) filterDuration.value = urlParams.get('duration');
    if (urlParams.has('type')) filterType.value = urlParams.get('type');
    if (urlParams.has('time_period')) filterTimePeriod.value = urlParams.get('time_period');
    
    // Highlight active quick filter button based on status
    highlightActiveStatusFilter(urlParams.get('status'));
    
    // Initialize the filter section to be visible if any filters are applied
    if (urlParams.has('category') || urlParams.has('status') || urlParams.has('priority') || 
        urlParams.has('duration') || urlParams.has('type') || urlParams.has('time_period')) {
        const filterSection = getElement('filterSection');
        if (filterSection) {
            // Only auto-expand advanced filters if it's not a simple status filter
            // This prevents both quick filters and advanced filters from being visible at once
            if (!(urlParams.size === 2 && urlParams.has('date') && urlParams.has('status'))) {
                const bsCollapse = new bootstrap.Collapse(filterSection, { toggle: true });
            }
            applyTaskFilters();
        } else {
            // If the advanced filter section doesn't exist, still apply filters
            applyTaskFilters();
        }
    }
}

function applyTaskFilters() {
    // Get filter values
    const categoryValue = getElement('filterCategory').value;
    const statusValue = getElement('filterStatus').value;
    const priorityValue = getElement('filterPriority').value;
    const durationValue = getElement('filterDuration').value;
    const typeValue = getElement('filterType').value;
    const timeValue = getElement('filterTimePeriod').value;
    
    // Get all task cards
    const taskCards = document.querySelectorAll('.task-card');
    
    // Track if we found any matches
    let matchesFound = false;
    
    // Loop through each task card
    taskCards.forEach(card => {
        // Extract data attributes
        const categoryId = card.getAttribute('data-category-id');
        const taskStatus = card.getAttribute('data-status');
        const taskPriority = card.getAttribute('data-priority');
        const taskDuration = parseInt(card.getAttribute('data-duration') || '0');
        const taskType = card.getAttribute('data-task-type');
        const taskTime = card.getAttribute('data-time');
        
        // Check if the card matches all selected filters
        let matchesCategory = categoryValue === '' || categoryId === categoryValue;
        let matchesStatus = statusValue === '' || taskStatus === statusValue;
        let matchesPriority = priorityValue === '' || taskPriority === priorityValue;
        
        // Duration filter logic
        let matchesDuration = true;
        if (durationValue !== '') {
            if (durationValue === 'short' && taskDuration >= 15) matchesDuration = false;
            else if (durationValue === 'medium' && (taskDuration < 15 || taskDuration > 30)) matchesDuration = false;
            else if (durationValue === 'long' && taskDuration <= 30) matchesDuration = false;
        }
        
        let matchesType = typeValue === '' || taskType === typeValue;
        
        // Time period filter logic
        let matchesTimePeriod = true;
        if (timeValue !== '') {
            if (timeValue === 'morning' && taskTime !== 'morning') matchesTimePeriod = false;
            else if (timeValue === 'evening' && taskTime !== 'evening') matchesTimePeriod = false;
            else if (timeValue === 'no_time' && taskTime !== '') matchesTimePeriod = false;
        }
        
        // Determine if card should be visible
        const isVisible = matchesCategory && matchesStatus && matchesPriority && 
                          matchesDuration && matchesType && matchesTimePeriod;
        
        // Update visibility
        card.style.display = isVisible ? '' : 'none';
        
        // Track if we found any matches
        if (isVisible) matchesFound = true;
    });
    
    // Show message for each section if no tasks match
    updateEmptySectionMessages('morning-tasks', matchesFound);
    updateEmptySectionMessages('evening-tasks', matchesFound);
    
    // Update URL with filter parameters (for sharing/bookmarking)
    updateURLWithFilters(categoryValue, statusValue, priorityValue, durationValue, typeValue, timeValue);
}

function resetTaskVisibility() {
    // Show all tasks
    const taskCards = document.querySelectorAll('.task-card');
    taskCards.forEach(card => {
        card.style.display = '';
    });
    
    // Remove any "no tasks match" messages
    document.querySelectorAll('.no-matching-tasks').forEach(el => el.remove());
    
    // Remove filters from URL
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.delete('category');
    urlParams.delete('status');
    urlParams.delete('priority');
    urlParams.delete('duration');
    urlParams.delete('type');
    urlParams.delete('time_period');
    
    // Get only the date parameter
    const dateParam = urlParams.has('date') ? `?date=${urlParams.get('date')}` : '';
    
    // Update URL without reloading page
    history.replaceState(null, '', `${window.location.pathname}${dateParam}`);
}

function updateEmptySectionMessages(sectionId, matchesFound) {
    const section = getElement(sectionId);
    if (!section) return;
    
    // Remove any existing "no tasks match" messages
    const existingMsg = section.querySelector('.no-matching-tasks');
    if (existingMsg) existingMsg.remove();
    
    // If no tasks match and there's an empty message, replace it with "no tasks match" message
    const taskCards = section.querySelectorAll('.task-card:not([style*="display: none"])');
    if (taskCards.length === 0) {
        const emptySectionMsg = section.querySelector('.text-center.text-muted');
        if (emptySectionMsg) {
            emptySectionMsg.innerHTML = '<div class="no-matching-tasks text-center p-3"><i class="fas fa-filter fa-2x mb-2 text-muted"></i><p>No tasks match your filters</p></div>';
        }
    }
}

function updateURLWithFilters(category, status, priority, duration, type, timePeriod) {
    // Get the current URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Update or remove filter parameters
    if (category) urlParams.set('category', category); else urlParams.delete('category');
    if (status) urlParams.set('status', status); else urlParams.delete('status');
    if (priority) urlParams.set('priority', priority); else urlParams.delete('priority');
    if (duration) urlParams.set('duration', duration); else urlParams.delete('duration');
    if (type) urlParams.set('type', type); else urlParams.delete('type');
    if (timePeriod) urlParams.set('time_period', timePeriod); else urlParams.delete('time_period');
    
    // Update URL without reloading page
    const newParams = urlParams.toString();
    history.replaceState(null, '', `${window.location.pathname}${newParams ? '?' + newParams : ''}`);
}

/**
 * Highlights the active status filter button
 */
function highlightActiveStatusFilter(status) {
    // First reset all buttons
    document.querySelectorAll('.btn-status-filter').forEach(btn => {
        btn.classList.remove('active');
        
        // Remove outline classes and set them back to default
        btn.classList.remove('btn-primary', 'btn-secondary', 'btn-success', 'btn-danger', 'btn-warning');
        
        // Add appropriate outline class based on href
        if (btn.href.includes('status=completed')) {
            btn.classList.add('btn-outline-success');
        } else if (btn.href.includes('status=not_done')) {
            btn.classList.add('btn-outline-danger');
        } else if (btn.href.includes('status=snoozed')) {
            btn.classList.add('btn-outline-warning');
        } else if (btn.href.includes('status=pending')) {
            btn.classList.add('btn-outline-secondary');
        } else {
            btn.classList.add('btn-outline-primary');
        }
    });
    
    // Now highlight the active button
    if (status) {
        const activeBtn = document.querySelector(`.btn-status-filter[href*="status=${status}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
            
            // Remove outline class and add solid color
            if (activeBtn.classList.contains('btn-outline-success')) {
                activeBtn.classList.remove('btn-outline-success');
                activeBtn.classList.add('btn-success');
            } else if (activeBtn.classList.contains('btn-outline-danger')) {
                activeBtn.classList.remove('btn-outline-danger');
                activeBtn.classList.add('btn-danger');
            } else if (activeBtn.classList.contains('btn-outline-warning')) {
                activeBtn.classList.remove('btn-outline-warning');
                activeBtn.classList.add('btn-warning');
            } else if (activeBtn.classList.contains('btn-outline-secondary')) {
                activeBtn.classList.remove('btn-outline-secondary');
                activeBtn.classList.add('btn-secondary');
            }
        }
    } else {
        // If no status filter, highlight the "All Tasks" button
        const allTasksBtn = document.querySelector('.btn-status-filter:not([href*="status="])');
        if (allTasksBtn) {
            allTasksBtn.classList.add('active');
            allTasksBtn.classList.remove('btn-outline-primary');
            allTasksBtn.classList.add('btn-primary');
        }
    }
}
</script>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>