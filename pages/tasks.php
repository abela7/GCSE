<?php
// Set page title
$page_title = "Tasks";

// Set breadcrumbs
$breadcrumbs = [
    'Tasks' => null
];

// Set page actions
$page_actions = '
<button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
    <i class="fas fa-plus me-1"></i> Add Task
</button>
';

// Include database connection
require_once '../config/db_connect.php';

// Get tasks
$tasks_query = "SELECT t.*, c.name as category_name, c.color as category_color 
               FROM tasks t 
               JOIN task_categories c ON t.category_id = c.id 
               ORDER BY t.status ASC, t.due_date ASC, t.priority DESC";
$tasks_result = $conn->query($tasks_query);

// Get categories for dropdown
$categories_query = "SELECT * FROM task_categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);

// Include header
include '../includes/header.php';
?>

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
                        <label for="taskTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="taskTitle" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="taskDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="taskDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="taskCategory" class="form-label">Category</label>
                        <select class="form-select" id="taskCategory" name="category_id" required>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <option value="<?php echo $category['id']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="taskDueDate" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="taskDueDate" name="due_date">
                    </div>
                    
                    <div class="mb-3">
                        <label for="taskPriority" class="form-label">Priority</label>
                        <select class="form-select" id="taskPriority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container for JavaScript notifications -->
<div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>

<!-- Tasks List -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Tasks</h5>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                <i class="fas fa-plus me-1"></i> Add Task
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Filter Section -->
        <div class="row mb-4 g-3">
            <div class="col-md-4">
                <select class="form-select" id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php 
                    $categories_result->data_seek(0); // Reset pointer
                    while ($category = $categories_result->fetch_assoc()): 
                    ?>
                    <option value="<?php echo $category['id']; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="dateFilter">
                    <option value="">All Dates</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
        </div>

        <?php if ($tasks_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tasksTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;"></th>
                            <th>Task</th>
                            <th class="d-none d-md-table-cell">Due Date</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $tasks_result->data_seek(0); // Reset pointer
                        while ($task = $tasks_result->fetch_assoc()): 
                        ?>
                            <tr class="task-row <?php echo $task['status'] == 'completed' ? 'completed-task' : ''; ?>" 
                                data-category="<?php echo $task['category_id']; ?>"
                                data-status="<?php echo $task['status']; ?>"
                                data-due-date="<?php echo $task['due_date']; ?>">
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input task-checkbox" type="checkbox" value="<?php echo $task['id']; ?>" 
                                               <?php echo $task['status'] == 'completed' ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                        <?php if ($task['description']): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($task['description']); ?></small>
                                        <?php endif; ?>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            <span class="badge" style="background-color: <?php echo htmlspecialchars($task['category_color']); ?>">
                                                <?php echo htmlspecialchars($task['category_name']); ?>
                                            </span>
                                            <span class="badge bg-<?php echo $task['priority'] == 'high' ? 'danger' : ($task['priority'] == 'medium' ? 'warning' : 'info'); ?>">
                                                <?php echo ucfirst($task['priority']); ?>
                                            </span>
                                            <span class="badge bg-<?php echo $task['status'] == 'completed' ? 'success' : ($task['status'] == 'pending' ? 'secondary' : 'warning'); ?>">
                                                <?php echo ucfirst($task['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : '-'; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary edit-task" data-task-id="<?php echo $task['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger delete-task" data-task-id="<?php echo $task['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                <p class="text-muted">No tasks found. Click the "Add Task" button to create one.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Task filtering
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const dateFilter = document.getElementById('dateFilter');
    const taskRows = document.querySelectorAll('.task-row');
    
    function filterTasks() {
        const selectedCategory = categoryFilter.value;
        const selectedStatus = statusFilter.value;
        const selectedDate = dateFilter.value;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        taskRows.forEach(row => {
            const category = row.dataset.category;
            const status = row.dataset.status;
            const dueDate = row.dataset.dueDate;
            
            let showRow = true;
            
            // Category filter
            if (selectedCategory && category !== selectedCategory) {
                showRow = false;
            }
            
            // Status filter
            if (selectedStatus && status !== selectedStatus) {
                showRow = false;
            }
            
            // Date filter
            if (selectedDate && dueDate) {
                const taskDate = new Date(dueDate);
                taskDate.setHours(0, 0, 0, 0);
                
                switch(selectedDate) {
                    case 'today':
                        if (taskDate.getTime() !== today.getTime()) {
                            showRow = false;
                        }
                        break;
                    case 'week':
                        const weekStart = new Date(today);
                        weekStart.setDate(today.getDate() - today.getDay());
                        const weekEnd = new Date(weekStart);
                        weekEnd.setDate(weekStart.getDate() + 6);
                        if (taskDate < weekStart || taskDate > weekEnd) {
                            showRow = false;
                        }
                        break;
                    case 'month':
                        if (taskDate.getMonth() !== today.getMonth() || taskDate.getFullYear() !== today.getFullYear()) {
                            showRow = false;
                        }
                        break;
                    case 'overdue':
                        if (taskDate >= today || status === 'completed') {
                            showRow = false;
                        }
                        break;
                }
            }
            
            row.style.display = showRow ? '' : 'none';
        });
    }
    
    categoryFilter.addEventListener('change', filterTasks);
    statusFilter.addEventListener('change', filterTasks);
    dateFilter.addEventListener('change', filterTasks);
    
    // Task checkbox handling
    const taskCheckboxes = document.querySelectorAll('.task-checkbox');
    
    taskCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskId = this.value;
            const status = this.checked ? 'completed' : 'pending';
            
            // AJAX request to update task status
            fetch('../includes/update_task_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `task_id=${taskId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const row = this.closest('tr');
                    const statusBadges = row.querySelectorAll('.badge');
                    const statusBadge = statusBadges[statusBadges.length - 1]; // Get the last badge (status badge)
                    
                    if (status === 'completed') {
                        row.classList.add('completed-task');
                        statusBadge.className = 'badge bg-success';
                        statusBadge.textContent = 'Completed';
                    } else {
                        row.classList.remove('completed-task');
                        statusBadge.className = 'badge bg-secondary';
                        statusBadge.textContent = 'Pending';
                    }
                    
                    // Update data attribute for filtering
                    row.dataset.status = status;
                    
                    showAlert('Task status updated successfully!', 'success');
                } else {
                    showAlert('Error updating task status: ' + data.message, 'danger');
                    this.checked = !this.checked;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while updating task status.', 'danger');
                this.checked = !this.checked;
            });
        });
    });
    
    // Add Task Form Handling
    const addTaskForm = document.getElementById('addTaskForm');
    if (addTaskForm) {
        addTaskForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../includes/add_task.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Task added successfully!', 'success');
                    // Close modal and reload page
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addTaskModal'));
                    modal.hide();
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    showAlert('Error adding task: ' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred while adding the task.', 'danger');
            });
        });
    }
    
    // Edit Task Handling
    const editButtons = document.querySelectorAll('.edit-task');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            
            // Fetch task details
            fetch(`../includes/get_task.php?id=${taskId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate form with task data
                        document.getElementById('taskTitle').value = data.task.title;
                        document.getElementById('taskDescription').value = data.task.description || '';
                        document.getElementById('taskCategory').value = data.task.category_id;
                        document.getElementById('taskDueDate').value = data.task.due_date || '';
                        document.getElementById('taskPriority').value = data.task.priority;
                        
                        // Update modal title and form action
                        document.querySelector('#addTaskModal .modal-title').textContent = 'Edit Task';
                        document.getElementById('addTaskForm').dataset.taskId = taskId;
                        
                        // Show modal
                        const modal = new bootstrap.Modal(document.getElementById('addTaskModal'));
                        modal.show();
                    } else {
                        showAlert('Error fetching task details: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while fetching task details.', 'danger');
                });
        });
    });
    
    // Delete Task Handling
    const deleteButtons = document.querySelectorAll('.delete-task');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this task?')) {
                const taskId = this.dataset.taskId;
                
                fetch('../includes/delete_task.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `task_id=${taskId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove row from table
                        this.closest('tr').remove();
                        showAlert('Task deleted successfully!', 'success');
                    } else {
                        showAlert('Error deleting task: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred while deleting the task.', 'danger');
                });
            }
        });
    });
    
    // Function to show alerts
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container');
        if (!alertContainer) return;
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        alertContainer.appendChild(alert);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => {
                alertContainer.removeChild(alert);
            }, 150);
        }, 5000);
    }
});
</script>

<style>
/* Responsive styles */
@media (max-width: 768px) {
    .filter-section {
        margin-bottom: 1rem;
    }
    
    .table-responsive {
        margin: 0 -1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .btn-group {
        margin-top: 0.5rem;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
    }
}

/* Task row styles */
.task-row {
    transition: background-color 0.2s ease;
}

.task-row:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.completed-task {
    opacity: 0.7;
}

.completed-task td {
    text-decoration: line-through;
}

/* Badge styles */
.badge {
    font-weight: 500;
    padding: 0.5em 0.75em;
}

/* Form styles */
.form-control:focus, .form-select:focus {
    border-color: #cdaf56;
    box-shadow: 0 0 0 0.2rem rgba(205, 175, 86, 0.25);
}

/* Button styles */
.btn-primary {
    background-color: #cdaf56;
    border-color: #cdaf56;
}

.btn-primary:hover {
    background-color: #b89c4a;
    border-color: #b89c4a;
}

.btn-outline-primary {
    color: #cdaf56;
    border-color: #cdaf56;
}

.btn-outline-primary:hover {
    background-color: #cdaf56;
    border-color: #cdaf56;
    color: white;
}

/* Modal styles */
.modal-content {
    border-radius: 12px;
}

.modal-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.modal-footer {
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}
</style>

<?php
// Include footer
include '../includes/footer.php';

// Close database connection
close_connection($conn);
?>