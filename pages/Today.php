<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Get today's date
$today = date('Y-m-d');

// Fetch next exam
$next_exam_query = "
    SELECT *,
           DATEDIFF(exam_date, CURRENT_DATE) as days_left
    FROM exams 
    WHERE exam_date > CURRENT_DATE
    ORDER BY exam_date ASC 
    LIMIT 1
";
$next_exam = $conn->query($next_exam_query)->fetch_assoc();

// Fetch next assignment
$next_assignment_query = "
    SELECT a.*, u.unit_name,
           (a.completed_criteria / NULLIF(a.total_criteria, 0)) * 100 as completion_percentage
    FROM access_assignments a
    LEFT JOIN access_course_units u ON a.unit_id = u.id
    WHERE a.due_date > CURRENT_DATE
    ORDER BY a.due_date ASC
    LIMIT 1
";
$next_assignment = $conn->query($next_assignment_query)->fetch_assoc();

// Get today's tasks
$tasks_query = "
    SELECT t.*, tc.name as category_name, tc.color as category_color
    FROM tasks t 
    LEFT JOIN task_categories tc ON t.category_id = tc.id
    WHERE DATE(t.due_date) = CURRENT_DATE
    AND t.status != 'completed'
    ORDER BY t.priority DESC, t.due_time ASC
";
$tasks_result = $conn->query($tasks_query);
$tasks = $tasks_result->fetch_all(MYSQLI_ASSOC);

// Get today's habits
$habits_query = "
    SELECT h.*, hc.name as category_name, hc.color as category_color,
           hp.status as today_status
    FROM habits h
    LEFT JOIN habit_categories hc ON h.category_id = hc.id
    LEFT JOIN habit_progress hp ON h.id = hp.habit_id AND hp.date = CURRENT_DATE
    WHERE h.is_active = 1
    ORDER BY h.target_time ASC
";
$habits_result = $conn->query($habits_query);
$habits = $habits_result->fetch_all(MYSQLI_ASSOC);

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

$page_title = "Today's Overview";
require_once '../includes/header.php';
?>

<div class="today-page">
    <!-- Header Section -->
    <section class="hero-section py-4 mb-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-2">Today's Overview</h1>
                    <p class="lead mb-0"><?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="row g-4">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Next Exam Card -->
                <?php if ($next_exam): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Next Exam</h5>
                            <span class="badge bg-warning"><?php echo $next_exam['days_left']; ?> days left</span>
                        </div>
                        <h6><?php echo htmlspecialchars($next_exam['title']); ?></h6>
                        <p class="text-muted mb-0">
                            <?php echo date('F j, Y', strtotime($next_exam['exam_date'])); ?> at 
                            <?php echo date('g:i A', strtotime($next_exam['exam_date'])); ?>
                            (<?php echo $next_exam['duration']; ?> minutes)
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Today's Habits -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Today's Habits</h5>
                            <a href="habits.php" class="btn btn-sm btn-primary">Manage Habits</a>
                        </div>
                        <?php if (empty($habits)): ?>
                            <p class="text-muted mb-0">No habits set for today</p>
                        <?php else: ?>
                            <div class="habits-list">
                                <?php foreach ($habits as $habit): ?>
                                    <div class="habit-item d-flex align-items-center p-2 border-bottom">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input habit-checkbox" 
                                                   data-habit-id="<?php echo $habit['id']; ?>"
                                                   <?php echo $habit['today_status'] === 'completed' ? 'checked' : ''; ?>>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($habit['name']); ?></h6>
                                            <div class="d-flex align-items-center">
                                                <span class="badge" style="background-color: <?php echo $habit['category_color']; ?>">
                                                    <?php echo htmlspecialchars($habit['category_name']); ?>
                                                </span>
                                                <?php if ($habit['target_time']): ?>
                                                    <span class="ms-2 text-muted small">
                                                        <i class="far fa-clock"></i> 
                                                        <?php echo date('g:i A', strtotime($habit['target_time'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Today's Tasks -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Today's Tasks</h5>
                            <a href="tasks.php" class="btn btn-sm btn-primary">Add Task</a>
                        </div>
                        <?php if (empty($tasks)): ?>
                            <p class="text-muted mb-0">No pending tasks for today</p>
                        <?php else: ?>
                            <div class="task-list">
                                <?php foreach ($tasks as $task): ?>
                                    <div class="task-item d-flex align-items-center p-2 border-bottom">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input task-checkbox" 
                                                   data-task-id="<?php echo $task['id']; ?>">
                                        </div>
                                        <div class="ms-3">
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
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Today's English Practice -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Today's English Practice</h5>
                            <a href="EnglishPractice/daily_entry.php" class="btn btn-sm btn-primary">Add Items</a>
                        </div>
                        <?php if (empty($english_items)): ?>
                            <p class="text-muted mb-0">No practice items added today</p>
                        <?php else: ?>
                            <div class="practice-items">
                                <?php foreach ($english_items as $item): ?>
                                    <div class="practice-item card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-2"><?php echo htmlspecialchars($item['item_title']); ?></h6>
                                                    <p class="mb-1 small text-muted">
                                                        <?php echo htmlspecialchars($item['item_meaning']); ?>
                                                    </p>
                                                </div>
                                                <button class="btn btn-link text-warning p-0 toggle-favorite" 
                                                        data-item-id="<?php echo $item['id']; ?>">
                                                    <i class="<?php echo $item['is_favorite'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Next Assignment Card -->
                <?php if ($next_assignment): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Next Assignment</h5>
                        <h6><?php echo htmlspecialchars($next_assignment['title']); ?></h6>
                        <p class="text-muted mb-2">Due: <?php echo date('F j, Y', strtotime($next_assignment['due_date'])); ?></p>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo $next_assignment['completion_percentage']; ?>%"></div>
                        </div>
                        <p class="small text-muted mt-2 mb-0">
                            <?php echo number_format($next_assignment['completion_percentage'], 0); ?>% complete
                        </p>
                    </div>
                </div>
                <?php endif; ?>
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
    padding-bottom: 2rem;
}

.hero-section {
    background: linear-gradient(135deg, var(--accent-color), var(--accent-dark));
    color: white;
}

.card {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.progress {
    background-color: #e9ecef;
    border-radius: 1rem;
}

.progress-bar {
    background-color: var(--accent-color);
}

.task-item:hover, .habit-item:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.task-checkbox, .habit-checkbox {
    border-color: var(--accent-color);
}

.task-checkbox:checked, .habit-checkbox:checked {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
}

.practice-item {
    transition: transform 0.2s;
}

.practice-item:hover {
    transform: translateX(5px);
}

.btn-primary {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
}

.btn-primary:hover {
    background-color: var(--accent-dark);
    border-color: var(--accent-dark);
}

.badge {
    font-weight: 500;
    padding: 0.5em 0.75em;
}

.completion-message {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
    background-color: var(--accent-color);
    color: white;
    padding: 1rem 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
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
                        taskItem.style.opacity = '0.5';
                        setTimeout(() => {
                            taskItem.remove();
                            checkAllCompleted('.task-list', 'tasks');
                        }, 500);
                    } else {
                        this.checked = false;
                    }
                });
            }
        });
    });

    // Habit completion handling
    document.querySelectorAll('.habit-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const habitId = this.dataset.habitId;
            const habitItem = this.closest('.habit-item');
            
            if (this.checked) {
                fetch('update_habit_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `habit_id=${habitId}&status=completed`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        habitItem.style.opacity = '0.5';
                        setTimeout(() => {
                            habitItem.remove();
                            checkAllCompleted('.habits-list', 'habits');
                        }, 500);
                    } else {
                        this.checked = false;
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

    // Function to check if all items are completed
    function checkAllCompleted(containerSelector, type) {
        const container = document.querySelector(containerSelector);
        if (container && container.children.length === 0) {
            showCompletionMessage(`All ${type} completed for today!`);
        }
    }

    // Function to show completion message
    function showCompletionMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'completion-message';
        messageDiv.textContent = message;
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => messageDiv.remove(), 300);
        }, 3000);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 