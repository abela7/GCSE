<?php
require_once '../../includes/header.php';
require_once '../../includes/db_connect.php';

// Get all categories with their habits
$query = "SELECT 
            c.id as category_id, c.name as category_name, c.icon as category_icon, c.color as category_color,
            h.id as habit_id, h.name as habit_name, h.description, h.target_time, h.is_active,
            hpr.name as point_rule_name, hpr.completion_points
          FROM habit_categories c
          LEFT JOIN habits h ON c.id = h.category_id
          LEFT JOIN habit_point_rules hpr ON h.point_rule_id = hpr.id
          ORDER BY c.display_order, h.target_time";
$result = $conn->query($query);

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categoryId = $row['category_id'];
    if (!isset($categories[$categoryId])) {
        $categories[$categoryId] = [
            'name' => $row['category_name'],
            'icon' => $row['category_icon'],
            'color' => $row['category_color'],
            'habits' => []
        ];
    }
    if ($row['habit_id']) {
        $categories[$categoryId]['habits'][] = [
            'id' => $row['habit_id'],
            'name' => $row['habit_name'],
            'description' => $row['description'],
            'target_time' => $row['target_time'],
            'is_active' => $row['is_active'],
            'point_rule_name' => $row['point_rule_name'],
            'completion_points' => $row['completion_points']
        ];
    }
}
?>

<div class="container-fluid">
    <div class="page-header">
        <div class="d-flex justify-content-end mb-3">
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <a href="categories.php" class="btn btn-outline-primary">
                    <i class="fas fa-folder"></i>
                </a>
                <button type="button" class="btn btn-primary" onclick="addHabit()">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
    </div>

    <style>
        /* Enhanced button styles */
        .page-header {
            background: #f8f9fa;
            padding: 1rem;
            margin: -1rem -1rem 1rem -1rem;
        }

        .page-header .btn {
            width: 45px;
            height: 45px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border-width: 2px;
            font-size: 1.1rem;
            transition: all 0.2s ease;
        }

        .page-header .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .page-header .btn i {
            font-size: 1.2rem;
        }

        .page-header .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: black;
        }

        .page-header .btn-primary:hover {
            background-color: #c4a64d;
            border-color: #c4a64d;
        }

        @media (max-width: 576px) {
            .page-header {
                position: sticky;
                top: 0;
                z-index: 1000;
            }
            
            .page-header .btn {
                width: 38px;
                height: 38px;
                font-size: 0.95rem;
            }

            .page-header .btn i {
                font-size: 1rem;
            }

            .page-header .d-flex.gap-2 {
                gap: 0.5rem !important;
            }
        }
    </style>

    <!-- Categories and Habits -->
    <div class="d-flex flex-column gap-4">
        <?php foreach ($categories as $categoryId => $category): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 py-3" style="background: linear-gradient(to right, <?php echo $category['color']; ?>20, white);">
                <div class="d-flex align-items-center gap-3">
                    <div class="category-icon" style="color: <?php echo $category['color']; ?>">
                        <i class="<?php echo $category['icon']; ?> fa-lg"></i>
                    </div>
                    <h2 class="h5 mb-0 fw-bold"><?php echo htmlspecialchars($category['name']); ?></h2>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($category['habits'])): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    No habits in this category
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($category['habits'] as $habit): ?>
                    <div class="list-group-item border-0 px-4 py-3">
                        <div class="d-flex flex-column flex-sm-row gap-3">
                            <div class="flex-grow-1 min-width-0">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <h3 class="h6 mb-0 text-truncate fw-bold"><?php echo htmlspecialchars($habit['name']); ?></h3>
                                    <span class="badge rounded-pill <?php echo $habit['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $habit['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                <div class="d-flex align-items-center flex-wrap gap-3 text-muted mb-2">
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('g:i A', strtotime($habit['target_time'])); ?>
                                    </span>
                                    <span class="d-flex align-items-center gap-1">
                                        <i class="fas fa-star"></i>
                                        <?php echo $habit['point_rule_name']; ?> (<?php echo $habit['completion_points']; ?> pts)
                                    </span>
                                </div>
                                <?php if ($habit['description']): ?>
                                <div class="text-muted small"><?php echo htmlspecialchars($habit['description']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-2 align-items-start">
                                <button type="button" 
                                        class="btn btn-sm d-flex align-items-center gap-2" 
                                        style="background-color: var(--primary-color); color: black;"
                                        onclick="editHabit(<?php echo $habit['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                    <span class="d-none d-sm-inline">Edit</span>
                                </button>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-<?php echo $habit['is_active'] ? 'warning' : 'success'; ?> d-flex align-items-center gap-2"
                                        onclick="toggleActive(<?php echo $habit['id']; ?>, '<?php echo addslashes($habit['name']); ?>', <?php echo $habit['is_active']; ?>)">
                                    <i class="fas <?php echo $habit['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                    <span class="d-none d-sm-inline"><?php echo $habit['is_active'] ? 'Pause' : 'Activate'; ?></span>
                                </button>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger d-flex align-items-center gap-2"
                                        onclick="deleteHabit(<?php echo $habit['id']; ?>, '<?php echo addslashes($habit['name']); ?>')">
                                    <i class="fas fa-trash"></i>
                                    <span class="d-none d-sm-inline">Delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add/Edit Habit Modal -->
<div class="modal fade" id="habitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="habitModalTitle">Add New Habit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="habitForm" onsubmit="event.preventDefault(); saveHabit();">
                    <input type="hidden" name="id" id="habit_id">
                    <div class="mb-4">
                        <label class="form-label fw-medium">Habit Name</label>
                        <input type="text" class="form-control form-control-lg" name="name" id="habit_name" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">Category</label>
                        <select class="form-select form-select-lg" name="category_id" id="habit_category_id" required>
                            <?php
                            foreach ($categories as $categoryId => $category) {
                                echo '<option value="' . $categoryId . '" data-icon="' . $category['icon'] . '" data-color="' . $category['color'] . '">';
                                echo '<i class="' . $category['icon'] . '"></i> ' . htmlspecialchars($category['name']);
                                echo '</option>';
                            }
                            ?>
                        </select>
                        <div class="form-text mt-2">
                            <div class="d-flex align-items-center gap-2">
                                <i id="selectedCategoryIcon" class=""></i>
                                <span>Icons are managed through categories. Visit the <a href="categories.php">Categories</a> page to manage icons.</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">Point Rule</label>
                        <select class="form-select form-select-lg" name="point_rule_id" id="habit_point_rule_id" required>
                            <?php
                            $rules_query = "SELECT * FROM habit_point_rules ORDER BY completion_points";
                            $rules_result = $conn->query($rules_query);
                            while ($rule = $rules_result->fetch_assoc()):
                            ?>
                            <option value="<?php echo $rule['id']; ?>">
                                <?php echo htmlspecialchars($rule['name']); ?> 
                                (<?php echo $rule['completion_points']; ?> pts)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">Target Time</label>
                        <input type="time" class="form-control form-control-lg" name="target_time" id="habit_target_time" required>
                    </div>
                    
                    <?php include 'habit_schedule.php'; ?>
                    
                    <div class="mb-4">
                        <label class="form-label fw-medium">Description</label>
                        <textarea class="form-control" name="description" id="habit_description" rows="3"></textarea>
                    </div>
                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" id="habit_is_active" value="1" checked>
                            <label class="form-check-label fw-medium">Active</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="habitForm" class="btn" style="background-color: var(--primary-color); color: black;">
                    Save Habit
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Base Styles */
:root {
    --primary-color: #cdaf56;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
    --text-color: #2d3436;
    --text-muted: #636e72;
    --border-radius: 16px;
    --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --transition-speed: 0.3s;
}

body {
    color: var(--text-color);
    background-color: #f8f9fa;
}

/* Card Styles */
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

/* Category Icon */
.category-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(0, 0, 0, 0.03), rgba(0, 0, 0, 0.06));
    border-radius: 14px;
    transition: all var(--transition-speed);
}

/* List Group Styles */
.list-group-item {
    transition: all var(--transition-speed);
}

.list-group-item:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

/* Button Styles */
.btn {
    border-radius: 12px;
    transition: all var(--transition-speed);
    font-weight: 500;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-outline-success {
    border-width: 2px;
}

.btn-outline-warning {
    border-width: 2px;
}

.btn-outline-danger {
    border-width: 2px;
}

/* Badge Styles */
.badge {
    font-weight: 500;
    padding: 0.5em 0.75em;
}

/* Modal Styles */
.modal-content {
    border-radius: var(--border-radius);
}

.form-control, .form-select {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    transition: all var(--transition-speed);
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(205, 175, 86, 0.25);
}

.form-check-input:checked {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

/* Responsive Styles */
@media (min-width: 992px) {
    .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .category-icon {
        width: 56px;
        height: 56px;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .category-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
    }
    
    .btn {
        padding: 0.5rem;
        font-size: 0.9rem;
    }
    
    .btn i {
        font-size: 1rem;
    }
    
    h1.h4 {
        font-size: 1.25rem;
    }
    
    .list-group-item {
        padding: 1rem !important;
    }

    .d-flex.gap-2 {
        gap: 0.5rem !important;
    }

    .btn {
        min-width: 35px;
        height: 35px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    .btn i {
        font-size: 0.95rem;
        margin: 0;
    }

    .list-group-item .d-flex.gap-2 {
        justify-content: center;
        width: 100%;
        margin-top: 1rem;
    }

    .list-group-item .d-flex.flex-column.flex-sm-row {
        gap: 0.5rem !important;
    }

    /* Header buttons */
    .page-header .d-flex.gap-2 {
        gap: 0.5rem !important;
    }

    .page-header .btn {
        width: 45px;
        height: 45px;
        border-width: 2px;
    }
}

@media (max-width: 768px) {
    .btn {
        padding: 0.375rem 0.75rem;
    }
    .btn i {
        font-size: 1.1rem;
    }

    .d-flex.flex-column.flex-sm-row {
        flex-direction: column !important;
    }

    .list-group-item {
        padding: 1rem;
    }

    .list-group-item .d-flex.gap-2 {
        justify-content: center;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(0,0,0,0.05);
        margin-top: 0.5rem;
    }

    .btn {
        flex: 1;
        max-width: 100px;
        margin: 0 0.25rem;
    }
}

/* Animation Keyframes */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.3s ease-out;
}

/* Add page header class */
.page-header {
    position: sticky;
    top: 0;
    background: #f8f9fa;
    z-index: 1000;
    padding: 1rem 0;
}
</style>

<script>
// Initialize habit modal
let habitModal;
document.addEventListener('DOMContentLoaded', function() {
    habitModal = new bootstrap.Modal(document.getElementById('habitModal'));
    
    // Initialize category icon display
    const categorySelect = document.getElementById('habit_category_id');
    const iconDisplay = document.getElementById('selectedCategoryIcon');
    
    if (categorySelect && iconDisplay) {
        function updateSelectedCategoryIcon() {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            const icon = selectedOption.getAttribute('data-icon');
            const color = selectedOption.getAttribute('data-color');
            iconDisplay.className = icon + ' fa-lg';
            iconDisplay.style.color = color;
        }
        
        categorySelect.addEventListener('change', updateSelectedCategoryIcon);
        updateSelectedCategoryIcon();
    }
});

// Edit habit
function editHabit(habitId) {
    // Fetch habit data
    fetch(`habit_actions.php?action=get&id=${habitId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const habit = data.habit;
                
                // Reset form and set title
                document.getElementById('habitForm').reset();
                document.getElementById('habitModalTitle').textContent = 'Edit Habit';
                
                // Fill form fields
                document.getElementById('habit_id').value = habit.id;
                document.getElementById('habit_name').value = habit.name;
                document.getElementById('habit_category_id').value = habit.category_id;
                document.getElementById('habit_point_rule_id').value = habit.point_rule_id;
                document.getElementById('habit_target_time').value = habit.target_time;
                document.getElementById('habit_description').value = habit.description || '';
                document.getElementById('habit_is_active').checked = habit.is_active == 1;
                
                // Get schedule information
                if (habit.scheduled_days && habit.scheduled_days.length > 0) {
                    // Specific days schedule
                    document.getElementById('schedule_specific_days').checked = true;
                    
                    // Check the appropriate day checkboxes
                    habit.scheduled_days.forEach(day => {
                        document.getElementById(`day_${day}`).checked = true;
                    });
                    
                    // Show the specific days panel
                    document.getElementById('specific_days_options').classList.remove('d-none');
                    document.getElementById('frequency_options').classList.add('d-none');
                } else if (habit.times_per_week) {
                    // Frequency-based schedule
                    document.getElementById('schedule_frequency').checked = true;
                    
                    // Set frequency options
                    document.getElementById('times_per_week').value = habit.times_per_week;
                    document.getElementById('week_starts_on').value = habit.week_starts_on || 0;
                    
                    // Show the frequency panel
                    document.getElementById('frequency_options').classList.remove('d-none');
                    document.getElementById('specific_days_options').classList.add('d-none');
                } else {
                    // Daily schedule (default)
                    document.getElementById('schedule_daily').checked = true;
                    document.getElementById('specific_days_options').classList.add('d-none');
                    document.getElementById('frequency_options').classList.add('d-none');
                }
                
                // Update category icon
                updateCategoryIcon();
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('habitModal'));
                modal.show();
            } else {
                alert('Error loading habit data: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading habit data');
        });
}

// Add new habit
function addHabit() {
    document.getElementById('habitModalTitle').textContent = 'Add New Habit';
    document.getElementById('habit_id').value = '';
    document.getElementById('habitForm').reset();
    document.getElementById('habit_is_active').checked = true;
    habitModal.show();
}

// Save habit
function saveHabit() {
    const form = document.getElementById('habitForm');
    if (!form) return;
    
    const formData = new FormData(form);
    const id = formData.get('id');
    formData.append('action', id ? 'update' : 'create');
    
    // Show loading state
    const saveButton = document.querySelector('[type="submit"]');
    saveButton.disabled = true;
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch('habit_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error saving habit');
            saveButton.disabled = false;
            saveButton.textContent = 'Save Habit';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving habit');
        saveButton.disabled = false;
        saveButton.textContent = 'Save Habit';
    });
}

// Delete habit
function deleteHabit(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetch('habit_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error deleting habit');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting habit');
        });
    }
}

// Toggle active status
function toggleActive(id, name, currentStatus) {
    const action = currentStatus ? 'pause' : 'activate';
    if (confirm(`Are you sure you want to ${action} "${name}"?`)) {
        const formData = new FormData();
        formData.append('action', 'toggle_active');
        formData.append('id', id);
        
        fetch('habit_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || `Error ${action}ing habit`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(`Error ${action}ing habit`);
        });
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?> 