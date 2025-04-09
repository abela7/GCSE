<?php
require_once '../../includes/header.php';
require_once '../../includes/db_connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id < 1) {
    $_SESSION['error'] = "Invalid task ID.";
    header('Location: index.php');
    exit;
}

// Get task details with category information
$task_query = "SELECT t.*, tc.name as category_name, tc.icon as category_icon, tc.color as category_color
               FROM tasks t
               LEFT JOIN task_categories tc ON t.category_id = tc.id
               WHERE t.id = ?";
$stmt = $conn->prepare($task_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    $_SESSION['error'] = "Task not found.";
    header('Location: index.php');
    exit;
}

// Get subtasks if any
$subtasks_query = "SELECT * FROM tasks WHERE parent_task_id = ? ORDER BY id";
$stmt = $conn->prepare($subtasks_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$subtasks_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Task</title>
    <style>
        :root {
            --primary-color: #cdaf56;
            --primary-hover: #b89c4a;
            --border-radius: 12px;
            --transition-speed: 0.2s;
            --card-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .page-header {
            background: white;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .header-icon {
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(205, 175, 86, 0.1);
            color: var(--primary-color);
        }

        .btn-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-custom:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .btn-custom-outline {
            background-color: transparent;
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-custom-outline:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .task-details {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .task-section {
            margin-bottom: 2rem;
        }

        .task-section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .info-value {
            font-weight: 500;
        }

        .priority-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .priority-high {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .priority-medium {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .priority-low {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }

        .status-in_progress {
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
        }

        .status-completed {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .subtasks-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .subtask-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .subtask-item:last-child {
            border-bottom: none;
        }

        .subtask-checkbox {
            width: 1.2rem;
            height: 1.2rem;
            border-radius: 4px;
            border: 2px solid var(--primary-color);
            cursor: pointer;
        }

        .subtask-title {
            flex-grow: 1;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-title {
                justify-content: center;
            }

            .task-details {
                padding: 1rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .subtask-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="page-header">
            <div class="header-title">
                <div class="header-icon">
                    <i class="<?php echo $task['category_icon'] ?? 'fas fa-tasks'; ?> fa-lg"></i>
                </div>
                <h1 class="h4 mb-0"><?php echo htmlspecialchars($task['title']); ?></h1>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-custom-outline">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back</span>
                </a>
                <button type="button" class="btn btn-custom" onclick="editTask(<?php echo $task['id']; ?>)">
                    <i class="fas fa-edit"></i>
                    <span>Edit</span>
                </button>
            </div>
        </div>

        <div class="task-details">
            <!-- Basic Information -->
            <div class="task-section">
                <h2 class="section-title">Basic Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Category</div>
                        <div class="info-value"><?php echo htmlspecialchars($task['category_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Priority</div>
                        <div class="info-value">
                            <span class="priority-badge priority-<?php echo strtolower($task['priority']); ?>">
                                <?php echo ucfirst($task['priority']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status-badge status-<?php echo $task['status']; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $task['status'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Task Type</div>
                        <div class="info-value"><?php echo ucwords(str_replace('_', ' ', $task['task_type'])); ?></div>
                    </div>
                </div>
            </div>

            <!-- Schedule -->
            <div class="task-section">
                <h2 class="section-title">Schedule</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Due Date</div>
                        <div class="info-value"><?php echo date('M j, Y', strtotime($task['due_date'])); ?></div>
                    </div>
                    <?php if ($task['due_time']): ?>
                    <div class="info-item">
                        <div class="info-label">Due Time</div>
                        <div class="info-value"><?php echo date('g:i A', strtotime($task['due_time'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <div class="info-label">Estimated Duration</div>
                        <div class="info-value"><?php echo $task['estimated_duration']; ?> minutes</div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <?php if ($task['description']): ?>
            <div class="task-section">
                <h2 class="section-title">Description</h2>
                <div class="task-description">
                    <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Subtasks -->
            <?php if ($subtasks_result->num_rows > 0): ?>
            <div class="task-section">
                <h2 class="section-title">Subtasks</h2>
                <ul class="subtasks-list">
                    <?php while ($subtask = $subtasks_result->fetch_assoc()): ?>
                    <li class="subtask-item">
                        <div class="subtask-checkbox" 
                             style="background-color: <?php echo $subtask['status'] === 'completed' ? 'var(--primary-color)' : 'transparent'; ?>"
                             onclick="toggleSubtask(<?php echo $subtask['id']; ?>, this)">
                        </div>
                        <div class="subtask-title"><?php echo htmlspecialchars($subtask['title']); ?></div>
                        <div class="subtask-status">
                            <span class="status-badge status-<?php echo $subtask['status']; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $subtask['status'])); ?>
                            </span>
                        </div>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function editTask(taskId) {
            window.location.href = 'edit_task.php?id=' + taskId;
        }

        function toggleSubtask(subtaskId, checkbox) {
            const newStatus = checkbox.style.backgroundColor === 'transparent' ? 'completed' : 'pending';
            checkbox.style.backgroundColor = newStatus === 'completed' ? 'var(--primary-color)' : 'transparent';

            // Update subtask status via AJAX
            fetch('update_subtask_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + subtaskId + '&status=' + newStatus
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert the checkbox if update failed
                    checkbox.style.backgroundColor = newStatus === 'completed' ? 'transparent' : 'var(--primary-color)';
                    alert('Error updating subtask status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert the checkbox on error
                checkbox.style.backgroundColor = newStatus === 'completed' ? 'transparent' : 'var(--primary-color)';
                alert('Error updating subtask status');
            });
        }
    </script>
</body>
</html> 