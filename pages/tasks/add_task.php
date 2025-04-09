<?php
require_once __DIR__ . '/../../../includes/auth_check.php';

// Include required files
require_once '../../config/db_connect.php';

// Get all categories for the dropdown
$categories_query = "SELECT * FROM task_categories ORDER BY name";
$categories_result = $conn->query($categories_query);

// Get all tasks for parent task selection
$tasks_query = "SELECT id, title FROM tasks WHERE parent_task_id IS NULL ORDER BY title";
$tasks_result = $conn->query($tasks_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Task</title>
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

        .task-form {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .subtasks-container {
            margin-top: 1rem;
        }

        .subtask-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: start;
        }

        .subtask-item .form-control {
            flex-grow: 1;
        }

        .remove-subtask {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--border-radius);
            transition: all var(--transition-speed);
        }

        .remove-subtask:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-title {
                justify-content: center;
            }

            .task-form {
                padding: 1rem;
            }

            .subtask-item {
                flex-direction: column;
            }

            .remove-subtask {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="page-header">
            <div class="header-title">
                <div class="header-icon">
                    <i class="fas fa-plus fa-lg"></i>
                </div>
                <h1 class="h4 mb-0">Add Task</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-custom-outline">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        <form class="task-form" method="POST" action="save_task.php">
            <!-- Basic Information -->
            <div class="form-section">
                <h2 class="form-section-title">Basic Information</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" required>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <!-- Schedule -->
            <div class="form-section">
                <h2 class="form-section-title">Schedule</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" name="due_date" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Due Time (optional)</label>
                        <input type="time" class="form-control" name="due_time">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Estimated Duration (minutes)</label>
                        <input type="number" class="form-control" name="estimated_duration" min="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority" required>
                            <option value="high">High</option>
                            <option value="medium" selected>Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Task Type and Parent -->
            <div class="form-section">
                <h2 class="form-section-title">Task Type</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="task_type" required>
                            <option value="one_time">One Time</option>
                            <option value="recurring">Recurring</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parent Task (optional)</label>
                        <select class="form-select" name="parent_task_id">
                            <option value="">None</option>
                            <?php while ($task = $tasks_result->fetch_assoc()): ?>
                            <option value="<?php echo $task['id']; ?>">
                                <?php echo htmlspecialchars($task['title']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Subtasks -->
            <div class="form-section">
                <h2 class="form-section-title">Subtasks</h2>
                <div id="subtasksContainer" class="subtasks-container">
                    <!-- Subtasks will be added here -->
                </div>
                <button type="button" class="btn btn-custom-outline" onclick="addSubtask()">
                    <i class="fas fa-plus"></i>
                    <span>Add Subtask</span>
                </button>
            </div>

            <!-- Submit Button -->
            <div class="d-grid">
                <button type="submit" class="btn btn-custom">
                    <i class="fas fa-save"></i>
                    <span>Create Task</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        function addSubtask() {
            const container = document.getElementById('subtasksContainer');
            const subtaskIndex = container.children.length;

            const subtaskItem = document.createElement('div');
            subtaskItem.className = 'subtask-item';
            subtaskItem.innerHTML = `
                <input type="text" class="form-control" name="subtasks[${subtaskIndex}][title]" placeholder="Subtask title" required>
                <button type="button" class="remove-subtask" onclick="removeSubtask(this)">
                    <i class="fas fa-times"></i>
                </button>
            `;

            container.appendChild(subtaskItem);
        }

        function removeSubtask(button) {
            const subtaskItem = button.parentElement;
            subtaskItem.remove();
            
            // Reindex remaining subtasks
            const container = document.getElementById('subtasksContainer');
            const subtasks = container.getElementsByClassName('subtask-item');
            
            Array.from(subtasks).forEach((item, index) => {
                const input = item.querySelector('input');
                input.name = `subtasks[${index}][title]`;
            });
        }

        // Set default due date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="due_date"]').value = today;
        });
    </script>
</body>
</html> 