<?php
session_start();
require_once '../includes/db_connect.php';

// Get next exam
$exam_query = "SELECT * FROM exams WHERE exam_date >= CURRENT_DATE ORDER BY exam_date ASC LIMIT 1";
$exam_result = $conn->query($exam_query);
$next_exam = $exam_result->fetch_assoc();

// Get next assignment
$assignment_query = "SELECT * FROM access_assignments WHERE due_date >= CURRENT_DATE AND status != 'Completed' ORDER BY due_date ASC LIMIT 1";
$assignment_result = $conn->query($assignment_query);
$next_assignment = $assignment_result->fetch_assoc();

// Get today's tasks
$tasks_query = "SELECT t.*, tc.name as category_name, tc.color as category_color 
                FROM tasks t 
                LEFT JOIN task_categories tc ON t.category_id = tc.id 
                WHERE DATE(t.due_date) = CURRENT_DATE AND t.status != 'Completed' 
                ORDER BY t.due_date ASC";
$tasks_result = $conn->query($tasks_query);

// Get today's habits - updated query
$habits_query = "SELECT h.*, hc.name as category_name, hc.color as category_color,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM habit_completions 
                        WHERE habit_id = h.id 
                        AND DATE(completion_date) = CURRENT_DATE
                    ) THEN 'Completed'
                    ELSE 'Pending'
                END as today_status
                FROM habits h
                LEFT JOIN habit_categories hc ON h.category_id = hc.id
                WHERE h.is_active = 1
                ORDER BY h.name ASC";
$habits_result = $conn->query($habits_query);

// Get today's English practice
$practice_query = "SELECT p.*, pc.name as category_name, 
                  (SELECT COUNT(*) FROM favorite_practice_items WHERE practice_item_id = p.id) as is_favorite
                  FROM practice_items p
                  LEFT JOIN practice_categories pc ON p.category_id = pc.id
                  WHERE DATE(p.created_at) = CURRENT_DATE
                  ORDER BY p.created_at DESC";
$practice_result = $conn->query($practice_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today - Study Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --accent-color: #cdaf56;
            --accent-light: #e6d7a3;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        
        .btn-accent {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
        }
        
        .btn-accent:hover {
            background-color: var(--accent-light);
            border-color: var(--accent-light);
            color: white;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        
        .completion-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Next Exam -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Next Exam</span>
                        <?php if ($next_exam): ?>
                            <span class="badge bg-warning"><?php echo ceil((strtotime($next_exam['exam_date']) - time()) / (60 * 60 * 24)); ?> days left</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($next_exam): ?>
                            <h5 class="card-title"><?php echo htmlspecialchars($next_exam['title']); ?></h5>
                            <p class="card-text">
                                <i class="bi bi-calendar"></i> <?php echo date('F j, Y', strtotime($next_exam['exam_date'])); ?><br>
                                <i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($next_exam['exam_time'])); ?><br>
                                <i class="bi bi-hourglass"></i> <?php echo $next_exam['duration']; ?> minutes
                            </p>
                        <?php else: ?>
                            <p class="text-muted">No upcoming exams</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Today's Tasks -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Today's Tasks</span>
                        <a href="tasks.php" class="btn btn-sm btn-accent">
                            <i class="bi bi-plus"></i> Add Task
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($tasks_result->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while ($task = $tasks_result->fetch_assoc()): ?>
                                    <a href="tasks.php?id=<?php echo $task['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <input type="checkbox" class="form-check-input task-checkbox" 
                                                   data-task-id="<?php echo $task['id']; ?>">
                                            <span class="ms-2"><?php echo htmlspecialchars($task['title']); ?></span>
                                            <?php if ($task['category_name']): ?>
                                                <span class="badge" style="background-color: <?php echo $task['category_color']; ?>">
                                                    <?php echo htmlspecialchars($task['category_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('g:i A', strtotime($task['due_date'])); ?>
                                        </small>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No tasks for today</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Today's Habits -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Today's Habits</span>
                        <a href="habits.php" class="btn btn-sm btn-accent">
                            <i class="bi bi-plus"></i> Add Habit
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($habits_result->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while ($habit = $habits_result->fetch_assoc()): ?>
                                    <?php if ($habit['today_status'] !== 'Completed'): ?>
                                        <a href="habits.php?id=<?php echo $habit['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            <div>
                                                <input type="checkbox" class="form-check-input habit-checkbox" 
                                                       data-habit-id="<?php echo $habit['id']; ?>">
                                                <span class="ms-2"><?php echo htmlspecialchars($habit['name']); ?></span>
                                                <?php if ($habit['category_name']): ?>
                                                    <span class="badge" style="background-color: <?php echo $habit['category_color']; ?>">
                                                        <?php echo htmlspecialchars($habit['category_name']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                Target: <?php echo $habit['target_time']; ?> minutes
                                            </small>
                                        </a>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No habits set for today</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Today's English Practice -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Today's English Practice</span>
                        <a href="add_practice.php" class="btn btn-sm btn-accent">
                            <i class="bi bi-plus"></i> Add Practice
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($practice_result->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while ($practice = $practice_result->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($practice['title']); ?></h6>
                                            <button class="btn btn-sm <?php echo $practice['is_favorite'] ? 'btn-warning' : 'btn-outline-warning'; ?> favorite-btn"
                                                    data-practice-id="<?php echo $practice['id']; ?>">
                                                <i class="bi bi-star<?php echo $practice['is_favorite'] ? '-fill' : ''; ?>"></i>
                                            </button>
                                        </div>
                                        <p class="mb-1"><?php echo htmlspecialchars($practice['meaning']); ?></p>
                                        <?php if ($practice['category_name']): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($practice['category_name']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No practice items for today</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-md-4">
                <!-- Next Assignment -->
                <div class="card mb-4">
                    <div class="card-header">Next Assignment</div>
                    <div class="card-body">
                        <?php if ($next_assignment): ?>
                            <h5 class="card-title"><?php echo htmlspecialchars($next_assignment['title']); ?></h5>
                            <p class="card-text">
                                <i class="bi bi-calendar"></i> Due: <?php echo date('F j, Y', strtotime($next_assignment['due_date'])); ?>
                            </p>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $next_assignment['completion_percentage']; ?>%">
                                    <?php echo $next_assignment['completion_percentage']; ?>%
                                </div>
                            </div>
                            <a href="assignment.php?id=<?php echo $next_assignment['id']; ?>" class="btn btn-accent">
                                View Details
                            </a>
                        <?php else: ?>
                            <p class="text-muted">No upcoming assignments</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Completion Message -->
    <div class="completion-message alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill"></i> All done for today! Great job!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Task completion handler
        document.querySelectorAll('.task-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function(e) {
                e.preventDefault(); // Prevent default checkbox behavior
                e.stopPropagation(); // Stop event from bubbling to the link
                const taskId = this.dataset.taskId;
                const status = this.checked ? 'Completed' : 'Pending';
                
                fetch('update_task_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `task_id=${taskId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the task item from the list
                        this.closest('.list-group-item').remove();
                        checkAllDone();
                    }
                });
            });
        });

        // Habit completion handler
        document.querySelectorAll('.habit-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function(e) {
                e.preventDefault(); // Prevent default checkbox behavior
                e.stopPropagation(); // Stop event from bubbling to the link
                const habitId = this.dataset.habitId;
                const status = this.checked ? 'Completed' : 'Pending';
                
                fetch('update_habit_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `habit_id=${habitId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the habit item from the list
                        this.closest('.list-group-item').remove();
                        checkAllDone();
                    }
                });
            });
        });

        // Check if all tasks and habits are completed
        function checkAllDone() {
            const tasks = document.querySelectorAll('.task-checkbox');
            const habits = document.querySelectorAll('.habit-checkbox');
            
            if (tasks.length === 0 && habits.length === 0) {
                document.querySelector('.completion-message').style.display = 'block';
            } else {
                document.querySelector('.completion-message').style.display = 'none';
            }
        }

        // Initialize completion check
        checkAllDone();
    </script>
</body>
</html> 