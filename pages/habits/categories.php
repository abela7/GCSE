<?php
require_once '../../includes/header.php';
require_once '../../includes/db_connect.php';

// Get all categories
$query = "SELECT c.*, COUNT(h.id) as habit_count
          FROM habit_categories c
          LEFT JOIN habits h ON c.id = h.category_id
          GROUP BY c.id
          ORDER BY c.display_order";
$result = $conn->query($query);

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get available Font Awesome icons (a curated list for habits)
$icons = [
    'fas fa-running' => 'Exercise',
    'fas fa-book' => 'Reading',
    'fas fa-bed' => 'Sleep',
    'fas fa-utensils' => 'Food',
    'fas fa-brain' => 'Learning',
    'fas fa-meditation' => 'Meditation',
    'fas fa-dumbbell' => 'Workout',
    'fas fa-pills' => 'Medicine',
    'fas fa-glass-water' => 'Hydration',
    'fas fa-person-walking' => 'Walking',
    'fas fa-house' => 'Home',
    'fas fa-briefcase' => 'Work',
    'fas fa-heart' => 'Health',
    'fas fa-book-open' => 'Study',
    'fas fa-pen' => 'Writing',
    'fas fa-music' => 'Music',
    'fas fa-palette' => 'Art',
    'fas fa-phone' => 'Communication',
    'fas fa-users' => 'Social',
    'fas fa-laptop-code' => 'Coding',
    'fas fa-broom' => 'Cleaning',
    'fas fa-list-check' => 'Planning',
    'fas fa-clock' => 'Time Management',
    'fas fa-hand-holding-heart' => 'Self Care',
    'fas fa-seedling' => 'Growth'
];
?>

<div class="container-fluid p-2 p-sm-3">
    <!-- Page Header -->
    <div class="d-flex flex-column gap-2 gap-sm-3 mb-3 mb-sm-4">
        <div class="d-flex align-items-center justify-content-between">
            <h1 class="h4 mb-0">Manage Categories</h1>
            <a href="manage_habits.php" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                <span class="d-none d-sm-inline">Back to Habits</span>
            </a>
        </div>
        
        <button type="button" class="btn d-flex align-items-center justify-content-center gap-2" 
                style="background-color: #cdaf56; color: black;"
                onclick="addCategory()">
            <i class="fas fa-plus"></i>
            <span>Add New Category</span>
        </button>
    </div>

    <!-- Categories List -->
    <div class="row g-3">
        <?php foreach ($categories as $category): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="category-icon" style="color: <?php echo $category['color']; ?>">
                            <i class="<?php echo $category['icon']; ?> fa-2x"></i>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <h3 class="h6 mb-1"><?php echo htmlspecialchars($category['name']); ?></h3>
                            <div class="small text-muted">
                                <?php echo $category['habit_count']; ?> habit<?php echo $category['habit_count'] != 1 ? 's' : ''; ?>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" 
                                    class="btn btn-sm" 
                                    style="background-color: #cdaf56; color: black;"
                                    onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                <i class="fas fa-edit"></i>
                                <span class="d-none d-sm-inline ms-1">Edit</span>
                            </button>
                            <?php if ($category['habit_count'] == 0): ?>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>')">
                                <i class="fas fa-trash"></i>
                                <span class="d-none d-sm-inline ms-1">Delete</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalTitle">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" name="id" id="category_id">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="name" id="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <input type="color" class="form-control form-control-color w-100" name="color" id="category_color" value="#cdaf56" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon</label>
                        <input type="hidden" name="icon" id="category_icon" required>
                        <div class="border rounded p-3">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div id="selectedIconPreview" class="category-icon">
                                    <i class="fas fa-question fa-2x"></i>
                                </div>
                                <div class="flex-grow-1">Selected Icon</div>
                            </div>
                            <div class="row g-2" id="iconGrid">
                                <?php foreach ($icons as $icon => $label): ?>
                                <div class="col-4 col-sm-3 col-md-2">
                                    <div class="icon-option p-2 rounded text-center" 
                                         data-icon="<?php echo $icon; ?>"
                                         onclick="selectIcon('<?php echo $icon; ?>')">
                                        <i class="<?php echo $icon; ?> fa-2x mb-1"></i>
                                        <div class="small text-muted text-truncate"><?php echo $label; ?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn" style="background-color: #cdaf56; color: black;" onclick="saveCategory()">
                    Save Category
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.category-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.03);
    border-radius: 12px;
}

.icon-option {
    cursor: pointer;
    transition: all 0.2s;
}

.icon-option:hover {
    background-color: rgba(0, 0, 0, 0.03);
}

.icon-option.selected {
    background-color: #cdaf56;
    color: black;
}

.icon-option.selected .text-muted {
    color: black !important;
}

@media (max-width: 576px) {
    .category-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
    }
    
    .btn-sm {
        padding: 0.35rem 0.7rem !important;
    }
    
    .btn-sm i {
        font-size: 1rem !important;
    }
}
</style>

<script>
// Initialize category modal
let categoryModal;
document.addEventListener('DOMContentLoaded', function() {
    categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    
    // Add form submit handler
    const categoryForm = document.getElementById('categoryForm');
    if (categoryForm) {
        categoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveCategory();
        });
    }
    
    // Add color change handler
    const colorInput = document.getElementById('category_color');
    if (colorInput) {
        colorInput.addEventListener('input', function(e) {
            document.getElementById('selectedIconPreview').style.color = e.target.value;
        });
    }
});

// Select icon
function selectIcon(icon) {
    // Update hidden input
    document.getElementById('category_icon').value = icon;
    
    // Update preview
    const preview = document.getElementById('selectedIconPreview');
    preview.innerHTML = `<i class="${icon} fa-2x"></i>`;
    preview.style.color = document.getElementById('category_color').value;
    
    // Update selection styling
    document.querySelectorAll('.icon-option').forEach(opt => {
        opt.classList.remove('selected');
        if (opt.dataset.icon === icon) {
            opt.classList.add('selected');
        }
    });
}

// Add new category
function addCategory() {
    document.getElementById('categoryModalTitle').textContent = 'Add New Category';
    document.getElementById('category_id').value = '';
    document.getElementById('categoryForm').reset();
    document.getElementById('category_color').value = '#cdaf56';
    document.getElementById('selectedIconPreview').innerHTML = '<i class="fas fa-question fa-2x"></i>';
    document.getElementById('selectedIconPreview').style.color = '#cdaf56';
    document.querySelectorAll('.icon-option').forEach(opt => opt.classList.remove('selected'));
    categoryModal.show();
}

// Edit category
function editCategory(category) {
    document.getElementById('categoryModalTitle').textContent = 'Edit Category';
    document.getElementById('category_id').value = category.id;
    document.getElementById('category_name').value = category.name;
    document.getElementById('category_color').value = category.color;
    document.getElementById('category_icon').value = category.icon;
    
    // Update icon preview
    const preview = document.getElementById('selectedIconPreview');
    preview.innerHTML = `<i class="${category.icon} fa-2x"></i>`;
    preview.style.color = category.color;
    
    // Update icon selection
    document.querySelectorAll('.icon-option').forEach(opt => {
        opt.classList.remove('selected');
        if (opt.dataset.icon === category.icon) {
            opt.classList.add('selected');
        }
    });
    
    categoryModal.show();
}

// Save category
function saveCategory() {
    const form = document.getElementById('categoryForm');
    if (!form) return;
    
    const formData = new FormData(form);
    const id = formData.get('id');
    formData.append('action', id ? 'update' : 'create');
    
    // Show loading state
    const saveButton = document.querySelector('[onclick="saveCategory()"]');
    saveButton.disabled = true;
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    fetch('category_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error saving category');
            saveButton.disabled = false;
            saveButton.textContent = 'Save Category';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving category');
        saveButton.disabled = false;
        saveButton.textContent = 'Save Category';
    });
}

// Delete category
function deleteCategory(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetch('category_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error deleting category');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting category');
        });
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?> 