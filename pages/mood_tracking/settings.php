<?php
// Include required files
require_once __DIR__ . '/includes/functions.php';

// Set page title
$page_title = "Mood Tracker Settings";

// Include header
require_once __DIR__ . '/../../includes/header.php';

// Process form submission for new tag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_tag') {
    $tag_name = isset($_POST['tag_name']) ? trim($_POST['tag_name']) : null;
    $tag_category = !empty($_POST['tag_category']) ? trim($_POST['tag_category']) : null;
    $tag_color = isset($_POST['tag_color']) ? trim($_POST['tag_color']) : '#cdaf56';
    
    if (!empty($tag_name)) {
        $result = createMoodTag($tag_name, $tag_category, $tag_color);
        if ($result) {
            $success_message = "Tag added successfully!";
        } else {
            $error_message = "Failed to add tag. Please try again.";
        }
    } else {
        $error_message = "Tag name is required.";
    }
}

// Process form submission for updating tag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_tag') {
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : null;
    $tag_name = isset($_POST['tag_name']) ? trim($_POST['tag_name']) : null;
    $tag_category = !empty($_POST['tag_category']) ? trim($_POST['tag_category']) : null;
    $tag_color = isset($_POST['tag_color']) ? trim($_POST['tag_color']) : '#cdaf56';
    
    if ($tag_id && !empty($tag_name)) {
        $result = updateMoodTag($tag_id, $tag_name, $tag_category, $tag_color);
        if ($result) {
            $success_message = "Tag updated successfully!";
        } else {
            $error_message = "Failed to update tag. Please try again.";
        }
    } else {
        $error_message = "Tag ID and name are required.";
    }
}

// Process form submission for deleting tag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_tag') {
    $tag_id = isset($_POST['tag_id']) ? intval($_POST['tag_id']) : null;
    
    if ($tag_id) {
        $result = deleteMoodTag($tag_id);
        if ($result) {
            $success_message = "Tag deleted successfully!";
        } else {
            $error_message = "Failed to delete tag. Please try again.";
        }
    } else {
        $error_message = "Tag ID is required.";
    }
}

// Get all tags
$all_tags = getMoodTags();

// Get tag categories
$tag_categories = getMoodTagCategories();
?>

<style>
:root {
    --accent-color: #cdaf56;
    --accent-color-light: #e0cb8c;
    --accent-color-dark: #b09339;
}

/* General Styles */
.tag-card {
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    margin-bottom: 1rem;
    border-radius: 10px;
}
.tag-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Tag Badge */
.tag-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 50rem;
    font-size: 1rem;
    font-weight: 500;
    margin-right: 0.5rem;
    color: #fff;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

/* Color Picker */
.color-option {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: inline-block;
    margin: 0.5rem;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.color-option:hover {
    transform: scale(1.1);
}
.color-option.selected {
    border-color: #000;
    transform: scale(1.15);
}

/* Custom Buttons */
.btn-accent {
    background-color: var(--accent-color);
    border-color: var(--accent-color);
    color: #fff;
}
.btn-accent:hover {
    background-color: var(--accent-color-dark);
    border-color: var(--accent-color-dark);
    color: #fff;
}
.btn-outline-accent {
    color: var(--accent-color);
    border-color: var(--accent-color);
}
.btn-outline-accent:hover {
    background-color: var(--accent-color);
    color: #fff;
}

/* Category Headers */
.category-header {
    border-bottom: 2px solid var(--accent-color);
    padding-bottom: 0.5rem;
    margin-bottom: 1.5rem;
    font-weight: 600;
    color: #333;
}

/* Action Buttons */
.tag-actions {
    opacity: 0.7;
    transition: opacity 0.2s ease;
}
.tag-card:hover .tag-actions {
    opacity: 1;
}

/* Mobile Optimizations */
@media (max-width: 767.98px) {
    .tag-badge {
        padding: 0.4rem 0.8rem;
        font-size: 0.95rem;
        width: 100%;
        text-align: center;
        margin-bottom: 0.5rem;
    }
    .tag-actions {
        opacity: 1;
        display: flex;
        justify-content: center;
        width: 100%;
        margin-top: 0.5rem;
    }
    .color-option {
        width: 32px;
        height: 32px;
        margin: 0.3rem;
    }
    .modal-dialog {
        margin: 0.5rem;
    }
    .modal-content {
        border-radius: 15px;
    }
    .btn {
        padding: 0.5rem 1rem;
        font-size: 1rem;
    }
    .form-control {
        font-size: 1rem;
        padding: 0.75rem;
        height: auto;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0" style="color: var(--accent-color);">
                <i class="fas fa-cog me-2"></i>Mood Tracker Settings
            </h1>
            <p class="text-muted">Manage your mood tracking tags and preferences</p>
        </div>
        <div class="col-md-4 text-md-end text-center mt-3 mt-md-0">
            <a href="index.php" class="btn btn-outline-accent">
                <i class="fas fa-arrow-left me-1"></i>Dashboard
            </a>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Tag Management -->
        <div class="col-lg-8 col-md-7 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                        <h5 class="card-title mb-3 mb-md-0" style="color: var(--accent-color);">
                            <i class="fas fa-tags me-2"></i>Manage Tags
                        </h5>
                        <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addTagModal">
                            <i class="fas fa-plus me-1"></i>Add New Tag
                        </button>
                    </div>
                    
                    <?php if (!empty($all_tags)): ?>
                        <?php
                        // Group tags by category
                        $grouped_tags = [];
                        foreach ($all_tags as $tag) {
                            $category = $tag['category'] ?? 'Uncategorized';
                            if (!isset($grouped_tags[$category])) {
                                $grouped_tags[$category] = [];
                            }
                            $grouped_tags[$category][] = $tag;
                        }
                        
                        // Sort categories alphabetically, but keep Uncategorized at the end
                        uksort($grouped_tags, function($a, $b) {
                            if ($a === 'Uncategorized') return 1;
                            if ($b === 'Uncategorized') return -1;
                            return strcmp($a, $b);
                        });
                        ?>
                        
                        <?php foreach ($grouped_tags as $category => $tags): ?>
                            <div class="mb-4">
                                <h6 class="category-header"><?php echo htmlspecialchars($category); ?></h6>
                                <div class="row">
                                    <?php foreach ($tags as $tag): ?>
                                        <div class="col-xl-4 col-md-6 mb-3">
                                            <div class="card tag-card h-100">
                                                <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                                                    <span class="tag-badge" style="background-color: <?php echo $tag['color']; ?>">
                                                        <?php echo htmlspecialchars($tag['name']); ?>
                                                    </span>
                                                    <div class="tag-actions">
                                                        <button type="button" class="btn btn-sm btn-outline-accent me-1" 
                                                                onclick="editTag(<?php echo $tag['id']; ?>, '<?php echo addslashes($tag['name']); ?>', '<?php echo addslashes($tag['category'] ?? ''); ?>', '<?php echo $tag['color']; ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteTag(<?php echo $tag['id']; ?>, '<?php echo addslashes($tag['name']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-tags fa-3x text-muted"></i>
                            </div>
                            <p class="text-muted mb-3">No tags found</p>
                            <button type="button" class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#addTagModal">
                                <i class="fas fa-plus me-1"></i>Add Your First Tag
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Settings and Help -->
        <div class="col-lg-4 col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3" style="color: var(--accent-color);">
                        <i class="fas fa-info-circle me-2"></i>About Tags
                    </h5>
                    <p>Tags help you categorize and filter your mood entries. Use them to identify patterns in your mood based on different aspects of your life.</p>
                    <p>Examples of how to use tags:</p>
                    <ul>
                        <li>Track mood related to specific activities (Exercise, Reading)</li>
                        <li>Monitor how different areas of life affect your mood (Work, Family, Health)</li>
                        <li>Identify triggers for mood changes (Stress, Sleep, Nutrition)</li>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3" style="color: var(--accent-color);">
                        <i class="fas fa-palette me-2"></i>Color Guide
                    </h5>
                    <p>Using colors effectively can help you quickly identify different types of tags:</p>
                    <div class="mb-2">
                        <span class="tag-badge" style="background-color: #cdaf56">General</span>
                        <span class="small text-muted">- Default color</span>
                    </div>
                    <div class="mb-2">
                        <span class="tag-badge" style="background-color: #28a745">Health</span>
                        <span class="small text-muted">- For health-related tags</span>
                    </div>
                    <div class="mb-2">
                        <span class="tag-badge" style="background-color: #17a2b8">Family</span>
                        <span class="small text-muted">- For family-related tags</span>
                    </div>
                    <div class="mb-2">
                        <span class="tag-badge" style="background-color: #e83e8c">Relationship</span>
                        <span class="small text-muted">- For relationship-related tags</span>
                    </div>
                    <div class="mb-2">
                        <span class="tag-badge" style="background-color: #007bff">School</span>
                        <span class="small text-muted">- For academic-related tags</span>
                    </div>
                    <div class="mb-2">
                        <span class="tag-badge" style="background-color: #fd7e14">Work</span>
                        <span class="small text-muted">- For professional-related tags</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Tag Modal -->
<div class="modal fade" id="addTagModal" tabindex="-1" aria-labelledby="addTagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_tag">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTagModalLabel" style="color: var(--accent-color);">Add New Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Tag Name -->
                    <div class="mb-3">
                        <label for="tag_name" class="form-label">Tag Name</label>
                        <input type="text" class="form-control form-control-lg" id="tag_name" name="tag_name" required autocomplete="off">
                    </div>
                    
                    <!-- Tag Category -->
                    <div class="mb-3">
                        <label for="tag_category" class="form-label">Category (Optional)</label>
                        <input type="text" class="form-control form-control-lg" id="tag_category" name="tag_category" list="categories" autocomplete="off">
                        <datalist id="categories">
                            <?php foreach ($tag_categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <div class="form-text">Group related tags under categories</div>
                    </div>
                    
                    <!-- Tag Color -->
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <input type="hidden" id="tag_color" name="tag_color" value="#cdaf56">
                        <div class="d-flex flex-wrap justify-content-center">
                            <?php
                            $colors = [
                                '#cdaf56', // Accent color
                                '#28a745', // Green
                                '#17a2b8', // Teal
                                '#007bff', // Blue
                                '#6610f2', // Indigo
                                '#6f42c1', // Purple
                                '#e83e8c', // Pink
                                '#dc3545', // Red
                                '#fd7e14', // Orange
                                '#ffc107', // Yellow
                                '#6c757d', // Gray
                                '#343a40', // Dark
                            ];
                            
                            foreach ($colors as $index => $color):
                                $is_selected = ($index === 0); // Default to accent color
                            ?>
                                <div class="color-option <?php echo $is_selected ? 'selected' : ''; ?>" 
                                     style="background-color: <?php echo $color; ?>" 
                                     data-color="<?php echo $color; ?>"
                                     onclick="selectColor(this)"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent">Add Tag</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tag Modal -->
<div class="modal fade" id="editTagModal" tabindex="-1" aria-labelledby="editTagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_tag">
                <input type="hidden" id="edit_tag_id" name="tag_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTagModalLabel" style="color: var(--accent-color);">Edit Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Tag Name -->
                    <div class="mb-3">
                        <label for="edit_tag_name" class="form-label">Tag Name</label>
                        <input type="text" class="form-control form-control-lg" id="edit_tag_name" name="tag_name" required autocomplete="off">
                    </div>
                    
                    <!-- Tag Category -->
                    <div class="mb-3">
                        <label for="edit_tag_category" class="form-label">Category (Optional)</label>
                        <input type="text" class="form-control form-control-lg" id="edit_tag_category" name="tag_category" list="edit_categories" autocomplete="off">
                        <datalist id="edit_categories">
                            <?php foreach ($tag_categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <!-- Tag Color -->
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <input type="hidden" id="edit_tag_color" name="tag_color">
                        <div class="d-flex flex-wrap justify-content-center" id="edit_color_options">
                            <?php foreach ($colors as $color): ?>
                                <div class="color-option" 
                                     style="background-color: <?php echo $color; ?>" 
                                     data-color="<?php echo $color; ?>"
                                     onclick="selectEditColor(this)"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Tag Modal -->
<div class="modal fade" id="deleteTagModal" tabindex="-1" aria-labelledby="deleteTagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="delete_tag">
                <input type="hidden" id="delete_tag_id" name="tag_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTagModalLabel" style="color: #dc3545;">Delete Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the tag "<span id="delete_tag_name"></span>"?</p>
                    <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. The tag will be removed from all mood entries.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Tag</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Color selection for Add Tag modal
function selectColor(element) {
    // Remove selected class from all options
    document.querySelectorAll('.color-option').forEach(option => {
        if (option.parentElement.id !== 'edit_color_options') {
            option.classList.remove('selected');
        }
    });
    
    // Add selected class to clicked option
    element.classList.add('selected');
    
    // Update hidden input value
    document.getElementById('tag_color').value = element.dataset.color;
}

// Color selection for Edit Tag modal
function selectEditColor(element) {
    // Remove selected class from all options in edit modal
    document.querySelectorAll('#edit_color_options .color-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    element.classList.add('selected');
    
    // Update hidden input value
    document.getElementById('edit_tag_color').value = element.dataset.color;
}

// Edit tag function
function editTag(id, name, category, color) {
    // Set form values
    document.getElementById('edit_tag_id').value = id;
    document.getElementById('edit_tag_name').value = name;
    document.getElementById('edit_tag_category').value = category;
    document.getElementById('edit_tag_color').value = color;
    
    // Select the correct color option
    document.querySelectorAll('#edit_color_options .color-option').forEach(option => {
        option.classList.remove('selected');
        if (option.dataset.color === color) {
            option.classList.add('selected');
        }
    });
    
    // Show the modal
    var editModal = new bootstrap.Modal(document.getElementById('editTagModal'));
    editModal.show();
}

// Delete tag function
function deleteTag(id, name) {
    // Set form values
    document.getElementById('delete_tag_id').value = id;
    document.getElementById('delete_tag_name').textContent = name;
    
    // Show the modal
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteTagModal'));
    deleteModal.show();
}

// Improve mobile experience
document.addEventListener('DOMContentLoaded', function() {
    // Increase touch target sizes on mobile
    if (window.innerWidth < 768) {
        document.querySelectorAll('.btn-sm').forEach(btn => {
            btn.classList.remove('btn-sm');
        });
    }
    
    // Fix iOS zoom on input focus
    document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"]').forEach(input => {
        input.style.fontSize = '16px';
    });
});
</script>

<?php
// Include footer
require_once __DIR__ . '/../../includes/footer.php';
?>
