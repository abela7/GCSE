<?php
// Set page title
$page_title = "Mood Tracker Settings";

// Include database connection and functions
require_once '../../../config/db_connect.php';
require_once '../includes/functions.php';

// Process form submission for new tag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_tag') {
    $tag_name = isset($_POST['tag_name']) ? trim($_POST['tag_name']) : null;
    $tag_category = !empty($_POST['tag_category']) ? trim($_POST['tag_category']) : null;
    $tag_color = isset($_POST['tag_color']) ? trim($_POST['tag_color']) : '#6c757d';
    
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
    $tag_color = isset($_POST['tag_color']) ? trim($_POST['tag_color']) : '#6c757d';
    
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

// Include header
include '../../../includes/header.php';
?>

<style>
/* General Styles */
.tag-card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    margin-bottom: 1rem;
}
.tag-card:hover {
    transform: translateY(-2px);
}

/* Tag Badge */
.tag-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 50rem;
    font-size: 1rem;
    margin-right: 0.5rem;
}

/* Color Picker */
.color-option {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.5rem;
    cursor: pointer;
    border: 2px solid transparent;
}
.color-option.selected {
    border-color: #000;
}

/* Mobile Optimizations */
@media (max-width: 767.98px) {
    .tag-badge {
        padding: 0.3rem 0.6rem;
        font-size: 0.9rem;
    }
    .color-option {
        width: 25px;
        height: 25px;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-cog me-2"></i>Mood Tracker Settings
            </h1>
            <p class="text-muted">Manage your mood tracking tags and preferences</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="index.php" class="btn btn-outline-secondary">
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
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-tags me-2"></i>Manage Tags
                        </h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTagModal">
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
                                <h6 class="mb-3"><?php echo htmlspecialchars($category); ?></h6>
                                <div class="row">
                                    <?php foreach ($tags as $tag): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card tag-card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="tag-badge" style="background-color: <?php echo $tag['color']; ?>">
                                                            <?php echo htmlspecialchars($tag['name']); ?>
                                                        </span>
                                                        <div>
                                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
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
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted mb-3">No tags found</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTagModal">
                                <i class="fas fa-plus me-1"></i>Add Your First Tag
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Settings and Help -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-info-circle me-2"></i>About Tags
                    </h5>
                    <p>Tags help you categorize and filter your mood entries. Use them to identify patterns in your mood based on different aspects of your life.</p>
                    <p>Examples of how to use tags:</p>
                    <ul>
                        <li>Track mood related to specific activities (Exercise, Reading, Studying)</li>
                        <li>Monitor how different areas of life affect your mood (Work, Family, Health)</li>
                        <li>Identify triggers for mood changes (Stress, Sleep, Nutrition)</li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-palette me-2"></i>Color Guide
                    </h5>
                    <p>Using colors effectively can help you quickly identify different types of tags:</p>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_tag">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTagModalLabel">Add New Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Tag Name -->
                    <div class="mb-3">
                        <label for="tag_name" class="form-label">Tag Name</label>
                        <input type="text" class="form-control" id="tag_name" name="tag_name" required>
                    </div>
                    
                    <!-- Tag Category -->
                    <div class="mb-3">
                        <label for="tag_category" class="form-label">Category (Optional)</label>
                        <input type="text" class="form-control" id="tag_category" name="tag_category" list="categories">
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
                        <input type="hidden" id="tag_color" name="tag_color" value="#6c757d">
                        <div class="d-flex flex-wrap">
                            <?php
                            $colors = [
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
                                '#20c997'  // Cyan
                            ];
                            
                            foreach ($colors as $index => $color):
                                $is_selected = ($index === 9); // Default to gray
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
                    <button type="submit" class="btn btn-primary">Add Tag</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Tag Modal -->
<div class="modal fade" id="editTagModal" tabindex="-1" aria-labelledby="editTagModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="update_tag">
                <input type="hidden" id="edit_tag_id" name="tag_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTagModalLabel">Edit Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Tag Name -->
                    <div class="mb-3">
                        <label for="edit_tag_name" class="form-label">Tag Name</label>
                        <input type="text" class="form-control" id="edit_tag_name" name="tag_name" required>
                    </div>
                    
                    <!-- Tag Category -->
                    <div class="mb-3">
                        <label for="edit_tag_category" class="form-label">Category (Optional)</label>
                        <input type="text" class="form-control" id="edit_tag_category" name="tag_category" list="edit_categories">
                        <datalist id="edit_categories">
                            <?php foreach ($tag_categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <div class="form-text">Group related tags under categories</div>
                    </div>
                    
                    <!-- Tag Color -->
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <input type="hidden" id="edit_tag_color" name="tag_color" value="#6c757d">
                        <div class="d-flex flex-wrap">
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
                    <button type="submit" class="btn btn-primary">Update Tag</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Tag Modal -->
<div class="modal fade" id="deleteTagModal" tabindex="-1" aria-labelledby="deleteTagModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="delete_tag">
                <input type="hidden" id="delete_tag_id" name="tag_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTagModalLabel">Delete Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the tag "<span id="delete_tag_name"></span>"?</p>
                    <p class="text-danger">This action cannot be undone. All associations with mood entries will be removed.</p>
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
// Function to select color in add modal
function selectColor(element) {
    // Remove selected class from all color options
    document.querySelectorAll('#addTagModal .color-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    element.classList.add('selected');
    
    // Update hidden input value
    document.getElementById('tag_color').value = element.dataset.color;
}

// Function to select color in edit modal
function selectEditColor(element) {
    // Remove selected class from all color options
    document.querySelectorAll('#editTagModal .color-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Add selected class to clicked option
    element.classList.add('selected');
    
    // Update hidden input value
    document.getElementById('edit_tag_color').value = element.dataset.color;
}

// Function to open edit tag modal
function editTag(id, name, category, color) {
    document.getElementById('edit_tag_id').value = id;
    document.getElementById('edit_tag_name').value = name;
    document.getElementById('edit_tag_category').value = category;
    document.getElementById('edit_tag_color').value = color;
    
    // Select the correct color option
    document.querySelectorAll('#editTagModal .color-option').forEach(option => {
        option.classList.remove('selected');
        if (option.dataset.color === color) {
            option.classList.add('selected');
        }
    });
    
    // Open the modal
    new bootstrap.Modal(document.getElementById('editTagModal')).show();
}

// Function to open delete tag modal
function deleteTag(id, name) {
    document.getElementById('delete_tag_id').value = id;
    document.getElementById('delete_tag_name').textContent = name;
    
    // Open the modal
    new bootstrap.Modal(document.getElementById('deleteTagModal')).show();
}
</script>

<?php include '../../../includes/footer.php'; ?>
