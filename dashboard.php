<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Get today's date
$today = date('Y-m-d');

// Fetch today's tasks
$tasks_query = "
    SELECT t.*, tc.name as category_name, tc.color as category_color
    FROM tasks t
    LEFT JOIN task_categories tc ON t.category_id = tc.id
    WHERE DATE(t.due_date) = CURRENT_DATE
    AND t.status != 'completed'
    ORDER BY t.priority DESC, t.due_time ASC
";
$tasks_result = $conn->query($tasks_query);
$today_tasks = $tasks_result->fetch_all(MYSQLI_ASSOC);

// Fetch upcoming exams
$exams_query = "
    SELECT *, 
           DATEDIFF(exam_date, CURRENT_DATE) as days_left
    FROM exams 
    WHERE exam_date >= CURRENT_DATE
    ORDER BY exam_date ASC 
    LIMIT 5
";
$exams_result = $conn->query($exams_query);
$upcoming_exams = $exams_result->fetch_all(MYSQLI_ASSOC);

// Fetch English practice stats
$english_stats_query = "
    SELECT 
        COUNT(*) as total_items,
        SUM(CASE WHEN created_at = CURRENT_DATE THEN 1 ELSE 0 END) as items_today,
        (SELECT COUNT(*) FROM favorite_practice_items) as total_favorites
    FROM practice_items
";
$english_stats = $conn->query($english_stats_query)->fetch_assoc();

// Fetch assignment progress
$assignments_query = "
    SELECT a.*, u.unit_name,
           (a.completed_criteria / NULLIF(a.total_criteria, 0)) * 100 as completion_percentage
    FROM access_assignments a
    LEFT JOIN access_course_units u ON a.unit_id = u.id
    WHERE a.status != 'completed'
    ORDER BY a.due_date ASC
    LIMIT 5
";
$assignments_result = $conn->query($assignments_query);
$active_assignments = $assignments_result->fetch_all(MYSQLI_ASSOC);

// Get today's English practice words
$words_query = "
    SELECT pi.*, pc.name as category_name,
           CASE WHEN fpi.practice_item_id IS NOT NULL THEN 1 ELSE 0 END as is_favorite
    FROM practice_items pi
    LEFT JOIN practice_categories pc ON pi.category_id = pc.id
    LEFT JOIN favorite_practice_items fpi ON pi.id = fpi.practice_item_id
    WHERE DATE(pi.created_at) = CURRENT_DATE
    ORDER BY pi.created_at DESC
    LIMIT 5
";
$words_result = $conn->query($words_query);
$today_words = $words_result->fetch_all(MYSQLI_ASSOC);

$page_title = "Dashboard";
require_once 'includes/header.php';
?>

<div class="dashboard">
    <!-- Priority Tasks Section -->
    <section class="dashboard-section mb-4">
        <div class="container-fluid">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div class="p-4 bg-primary text-white rounded-top">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h2 class="h5 mb-0">Today's Priority Tasks</h2>
                                    <a href="tasks.php" class="btn btn-sm btn-outline-light">View All</a>
                                </div>
                            </div>
                            <?php if (empty($today_tasks)): ?>
                                <div class="p-4 text-center">
                                    <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                                    <p class="mb-0">All tasks completed for today!</p>
                                </div>
                            <?php else: ?>
                                <div class="task-list p-3">
                                    <?php foreach ($today_tasks as $task): ?>
                                        <div class="task-item d-flex align-items-center p-2 border-bottom">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input task-checkbox" 
                                                       data-task-id="<?php echo $task['id']; ?>"
                                                       id="task<?php echo $task['id']; ?>">
                                            </div>
                                            <div class="ms-3 flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
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
                                                </div>
                                            </div>
                                            <div class="task-actions">
                                                <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                                    <li><a class="dropdown-item" href="#"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                                </ul>
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
    </section>

    <!-- Exam Countdown & Stats -->
    <section class="dashboard-section mb-4">
        <div class="container-fluid">
            <div class="row g-3">
                <!-- Exam Countdown -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h3 class="h5 mb-4">Upcoming Exams</h3>
                            <?php if (empty($upcoming_exams)): ?>
                                <p class="text-muted">No upcoming exams scheduled.</p>
                            <?php else: ?>
                                <?php foreach ($upcoming_exams as $exam): ?>
                                    <div class="exam-countdown mb-3">
                                        <h6 class="mb-2"><?php echo htmlspecialchars($exam['exam_name']); ?></h6>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1" style="height: 8px;">
                                                <?php 
                                                $days_left = max(0, $exam['days_left']);
                                                $total_days = 90; // Assuming 90 days preparation period
                                                $progress = 100 - (($days_left / $total_days) * 100);
                                                ?>
                                                <div class="progress-bar bg-warning" style="width: <?php echo $progress; ?>%"></div>
                                            </div>
                                            <span class="ms-3 badge bg-warning">
                                                <?php echo $days_left; ?> days left
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="col-md-6">
                    <div class="row g-3">
                        <!-- English Practice Stats -->
                        <div class="col-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-book fa-2x text-primary"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-0">English Practice</h6>
                                            <small class="text-muted">Today's Progress</small>
                                        </div>
                                    </div>
                                    <h3 class="mb-0"><?php echo $english_stats['items_today']; ?></h3>
                                    <small class="text-muted">items added today</small>
                                </div>
                            </div>
                        </div>

                        <!-- Assignment Progress -->
                        <div class="col-6">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-tasks fa-2x text-success"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-0">Assignments</h6>
                                            <small class="text-muted">Active Progress</small>
                                        </div>
                                    </div>
                                    <?php
                                    $total_progress = 0;
                                    $assignment_count = count($active_assignments);
                                    foreach ($active_assignments as $assignment) {
                                        $total_progress += $assignment['completion_percentage'];
                                    }
                                    $average_progress = $assignment_count > 0 ? $total_progress / $assignment_count : 0;
                                    ?>
                                    <h3 class="mb-0"><?php echo number_format($average_progress, 1); ?>%</h3>
                                    <small class="text-muted">average completion</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Active Assignments -->
    <section class="dashboard-section mb-4">
        <div class="container-fluid">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h5 mb-0">Active Assignments</h3>
                        <a href="assignments.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <?php if (empty($active_assignments)): ?>
                        <p class="text-muted">No active assignments.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Assignment</th>
                                        <th>Due Date</th>
                                        <th>Progress</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_assignments as $assignment): ?>
                                        <tr>
                                            <td>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($assignment['unit_name']); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                $days_until_due = (strtotime($assignment['due_date']) - time()) / (60 * 60 * 24);
                                                $badge_class = $days_until_due < 7 ? 'bg-danger' : ($days_until_due < 14 ? 'bg-warning' : 'bg-info');
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo date('M j, Y', strtotime($assignment['due_date'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $assignment['completion_percentage']; ?>%"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo ucfirst($assignment['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Today's English Practice -->
    <section class="dashboard-section mb-4">
        <div class="container-fluid">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h5 mb-0">Today's English Practice</h3>
                        <a href="pages/EnglishPractice/practice.php" class="btn btn-sm btn-outline-primary">Practice Now</a>
                    </div>
                    <?php if (empty($today_words)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                            <p class="mb-4">No practice items added today.</p>
                            <a href="pages/EnglishPractice/daily_entry.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add New Items
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($today_words as $word): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <span class="badge bg-light text-dark">
                                                    <?php echo htmlspecialchars($word['category_name']); ?>
                                                </span>
                                                <button class="btn btn-link text-warning p-0 toggle-favorite" 
                                                        data-item-id="<?php echo $word['id']; ?>">
                                                    <i class="<?php echo $word['is_favorite'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                </button>
                                            </div>
                                            <h5 class="card-title mb-3"><?php echo htmlspecialchars($word['item_title']); ?></h5>
                                            <p class="card-text small text-muted mb-0">
                                                <?php echo htmlspecialchars(substr($word['item_meaning'], 0, 100)); ?>...
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
:root {
    --accent-color: #cdaf56;
    --accent-dark: #b69a45;
    --accent-light: #e6d5a7;
}

.dashboard {
    background-color: #f8f9fa;
    min-height: 100vh;
    padding: 1.5rem 0;
}

.dashboard-section {
    margin-bottom: 2rem;
}

.card {
    transition: transform 0.2s;
    border-radius: 0.75rem;
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

.badge {
    font-weight: 500;
    padding: 0.5em 0.75em;
}

.btn-outline-primary {
    color: var(--accent-color);
    border-color: var(--accent-color);
}

.btn-outline-primary:hover {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
    color: white;
}

.btn-primary {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
}

.btn-primary:hover {
    background-color: var(--accent-dark);
    border-color: var(--accent-dark);
}

.text-primary {
    color: var(--accent-color) !important;
}

@media (max-width: 768px) {
    .dashboard {
        padding: 1rem 0;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .table-responsive {
        margin: 0 -1rem;
        width: calc(100% + 2rem);
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
            
            if (this.checked) {
                // Animate task completion
                taskItem.style.opacity = '0.5';
                setTimeout(() => {
                    taskItem.style.height = taskItem.offsetHeight + 'px';
                    taskItem.style.height = '0';
                    taskItem.style.padding = '0';
                    taskItem.style.margin = '0';
                    taskItem.style.overflow = 'hidden';
                }, 300);

                // Update task status
                fetch('update_task_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `task_id=${taskId}&status=completed`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        setTimeout(() => taskItem.remove(), 500);
                    } else {
                        this.checked = false;
                        taskItem.style.opacity = '1';
                        alert('Error updating task status');
                    }
                });
            }
        });
    });

    // Favorite toggling
    document.querySelectorAll('.toggle-favorite').forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const icon = this.querySelector('i');
            
            fetch('pages/EnglishPractice/toggle_favorite.php', {
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
</script>

<?php require_once 'includes/footer.php'; ?> 