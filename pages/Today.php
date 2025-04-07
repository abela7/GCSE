<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Get today's date
$today = date('Y-m-d');

// Fetch today's English practice items
$english_query = "
    SELECT pi.*, pc.name as category_name,
           CASE WHEN fpi.practice_item_id IS NOT NULL THEN 1 ELSE 0 END as is_favorite
    FROM practice_items pi
    LEFT JOIN practice_categories pc ON pi.category_id = pc.id
    LEFT JOIN favorite_practice_items fpi ON pi.id = fpi.practice_item_id
    WHERE DATE(pi.created_at) = CURRENT_DATE
    ORDER BY pi.created_at DESC
";
$english_result = $conn->query($english_query);
$english_items = $english_result->fetch_all(MYSQLI_ASSOC);

// Get today's study time
$study_time_query = "
    SELECT 
        SUM(CASE WHEN subject_type = 'english' THEN duration_minutes ELSE 0 END) as english_minutes,
        SUM(CASE WHEN subject_type = 'math' THEN duration_minutes ELSE 0 END) as math_minutes
    FROM study_time_tracking
    WHERE DATE(study_date) = CURRENT_DATE
";
$study_time = $conn->query($study_time_query)->fetch_assoc();

// Get today's tasks
$tasks_query = "
    SELECT t.*, tc.name as category_name, tc.color as category_color
    FROM tasks t
    LEFT JOIN task_categories tc ON t.category_id = tc.id
    WHERE DATE(t.due_date) = CURRENT_DATE
    ORDER BY t.status ASC, t.priority DESC, t.due_time ASC
";
$tasks_result = $conn->query($tasks_query);
$tasks = $tasks_result->fetch_all(MYSQLI_ASSOC);

// Calculate task completion stats
$total_tasks = count($tasks);
$completed_tasks = array_reduce($tasks, function($carry, $task) {
    return $carry + ($task['status'] === 'completed' ? 1 : 0);
}, 0);
$completion_percentage = $total_tasks > 0 ? ($completed_tasks / $total_tasks) * 100 : 0;

$page_title = "Today's Overview";
require_once '../includes/header.php';
?>

<div class="today-page">
    <!-- Hero Section -->
    <section class="hero-section text-white py-5 mb-4">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 mb-2">Today's Focus</h1>
                    <p class="lead mb-0"><?php echo date('l, F j, Y'); ?></p>
                </div>
                <div class="col-md-6">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-book-reader"></i>
                                </div>
                                <div class="stat-content">
                                    <h3><?php echo number_format($study_time['english_minutes'] ?? 0); ?></h3>
                                    <p>Minutes on English</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <div class="stat-content">
                                    <h3><?php echo number_format($study_time['math_minutes'] ?? 0); ?></h3>
                                    <p>Minutes on Math</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container-fluid">
        <div class="row g-4">
            <!-- Tasks Column -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="h4 mb-1">Today's Tasks</h2>
                                <p class="text-muted mb-0">
                                    <?php echo $completed_tasks; ?> of <?php echo $total_tasks; ?> tasks completed
                                </p>
                            </div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                <i class="fas fa-plus me-2"></i>Add Task
                            </button>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress mb-4" style="height: 8px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo $completion_percentage; ?>%"></div>
                        </div>

                        <!-- Task List -->
                        <div class="task-list">
                            <?php if (empty($tasks)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                    <p class="mb-0">No tasks scheduled for today</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($tasks as $task): ?>
                                    <div class="task-item d-flex align-items-center p-3 border-bottom">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input task-checkbox" 
                                                   data-task-id="<?php echo $task['id']; ?>"
                                                   <?php echo $task['status'] === 'completed' ? 'checked' : ''; ?>>
                                        </div>
                                        <div class="ms-3 flex-grow-1">
                                            <h6 class="mb-1 <?php echo $task['status'] === 'completed' ? 'text-muted text-decoration-line-through' : ''; ?>">
                                                <?php echo htmlspecialchars($task['title']); ?>
                                            </h6>
                                            <div class="d-flex align-items-center">
                                                <span class="badge" style="background-color: <?php echo $task['category_color']; ?>">
                                                    <?php echo htmlspecialchars($task['category_name']); ?>
                                                </span>
                                                <?php if ($task['due_time']): ?>
                                                    <span class="ms-2 text-muted small">
                                                        <i class="far fa-clock"></i> 
                                                        <?php echo date('g:i A', strtotime($task['due_time'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($task['priority'] === 'high'): ?>
                                                    <span class="ms-2 badge bg-danger">High Priority</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="task-actions">
                                            <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" 
                                                       data-bs-target="#editTaskModal" 
                                                       data-task-id="<?php echo $task['id']; ?>">
                                                        <i class="fas fa-edit me-2"></i>Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="#" 
                                                       onclick="deleteTask(<?php echo $task['id']; ?>)">
                                                        <i class="fas fa-trash me-2"></i>Delete
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- English Practice Column -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="h4 mb-1">Today's English Practice</h2>
                                <p class="text-muted mb-0">
                                    <?php echo count($english_items); ?> items added today
                                </p>
                            </div>
                            <a href="EnglishPractice/daily_entry.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add Items
                            </a>
                        </div>

                        <?php if (empty($english_items)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                <p class="mb-4">No practice items added today</p>
                                <a href="EnglishPractice/daily_entry.php" class="btn btn-primary">
                                    Start Practice
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="practice-items">
                                <?php foreach ($english_items as $item): ?>
                                    <div class="practice-item card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <span class="badge bg-light text-dark">
                                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                                </span>
                                                <button class="btn btn-link text-warning p-0 toggle-favorite" 
                                                        data-item-id="<?php echo $item['id']; ?>">
                                                    <i class="<?php echo $item['is_favorite'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                </button>
                                            </div>
                                            <h5 class="card-title mb-2">
                                                <?php echo htmlspecialchars($item['item_title']); ?>
                                            </h5>
                                            <p class="card-text small text-muted mb-2">
                                                <?php echo htmlspecialchars($item['item_meaning']); ?>
                                            </p>
                                            <?php if ($item['item_example']): ?>
                                                <div class="example-text p-2 bg-light rounded small">
                                                    <i class="fas fa-quote-left text-muted me-2"></i>
                                                    <?php echo htmlspecialchars($item['item_example']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addTaskForm">
                    <div class="mb-3">
                        <label class="form-label">Task Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" required>
                            <!-- Add categories dynamically -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Time</label>
                        <input type="time" class="form-control" name="due_time">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitTask()">Add Task</button>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --accent-color: #cdaf56;
    --accent-dark: #b69a45;
    --accent-light: #e6d5a7;
}

.today-page {
    background-color: #f8f9fa;
    min-height: 100vh;
}

.hero-section {
    background: linear-gradient(135deg, var(--accent-color), var(--accent-dark));
    padding: 3rem 0;
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 1rem;
    padding: 1.5rem;
    backdrop-filter: blur(10px);
}

.stat-card .stat-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.stat-card h3 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.stat-card p {
    margin-bottom: 0;
    opacity: 0.8;
}

.card {
    border-radius: 1rem;
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-2px);
}

.progress {
    background-color: #e9ecef;
    border-radius: 1rem;
    overflow: hidden;
}

.progress-bar {
    background-color: var(--accent-color);
    transition: width 0.6s ease;
}

.task-item {
    transition: background-color 0.2s;
}

.task-item:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.task-checkbox {
    border-color: var(--accent-color);
}

.task-checkbox:checked {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
}

.practice-item {
    transition: transform 0.2s;
}

.practice-item:hover {
    transform: translateX(5px);
}

.example-text {
    border-left: 3px solid var(--accent-color);
}

.badge {
    font-weight: 500;
    padding: 0.5em 0.75em;
}

.btn-primary {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
}

.btn-primary:hover {
    background-color: var(--accent-dark);
    border-color: var(--accent-dark);
}

@media (max-width: 768px) {
    .hero-section {
        padding: 2rem 0;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Task completion handling
    document.querySelectorAll('.task-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const taskItem = this.closest('.task-item');
            const taskTitle = taskItem.querySelector('h6');
            
            fetch('update_task_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `task_id=${taskId}&status=${this.checked ? 'completed' : 'pending'}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    taskTitle.classList.toggle('text-muted');
                    taskTitle.classList.toggle('text-decoration-line-through');
                    
                    // Update progress
                    updateTaskProgress();
                } else {
                    this.checked = !this.checked;
                    alert('Error updating task status');
                }
            });
        });
    });

    // Favorite toggling
    document.querySelectorAll('.toggle-favorite').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const icon = this.querySelector('i');
            
            fetch('EnglishPractice/toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${itemId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    icon.classList.toggle('far');
                    icon.classList.toggle('fas');
                }
            });
        });
    });
});

function updateTaskProgress() {
    const totalTasks = document.querySelectorAll('.task-checkbox').length;
    const completedTasks = document.querySelectorAll('.task-checkbox:checked').length;
    const progressBar = document.querySelector('.progress-bar');
    const progressText = document.querySelector('.text-muted');
    
    const percentage = (completedTasks / totalTasks) * 100;
    progressBar.style.width = `${percentage}%`;
    progressText.textContent = `${completedTasks} of ${totalTasks} tasks completed`;
}

function submitTask() {
    const form = document.getElementById('addTaskForm');
    const formData = new FormData(form);
    
    fetch('add_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error adding task: ' + data.message);
        }
    });
}

function deleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task?')) {
        fetch('delete_task.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `task_id=${taskId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting task');
            }
        });
    }
}
</script>

<?php require_once '../includes/footer.php'; ?> 