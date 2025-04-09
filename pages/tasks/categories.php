<?php
require_once __DIR__ . '/../../../includes/auth_check.php';

// Include required files
require_once '../../config/db_connect.php';
require_once '../../includes/header.php';

// Get category data for editing if ID is provided
$editCategory = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT id, name, icon, color FROM task_categories WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $editCategory = $result->fetch_assoc();
    }
}

// Get all categories with task counts
$query = "SELECT c.*, COUNT(t.id) as task_count
          FROM task_categories c
          LEFT JOIN tasks t ON c.id = t.category_id
          GROUP BY c.id
          ORDER BY c.name";
$result = $conn->query($query);

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get available Font Awesome icons (a curated list for tasks)
$icons = [
    'fas fa-tasks' => 'Tasks',
    'fas fa-list-check' => 'Checklist',
    'fas fa-book' => 'Study',
    'fas fa-graduation-cap' => 'Education',
    'fas fa-laptop-code' => 'Coding',
    'fas fa-brain' => 'Learning',
    'fas fa-calendar' => 'Schedule',
    'fas fa-clock' => 'Time',
    'fas fa-briefcase' => 'Work',
    'fas fa-home' => 'Home',
    'fas fa-dumbbell' => 'Exercise',
    'fas fa-heart' => 'Health',
    'fas fa-users' => 'Social',
    'fas fa-shopping-cart' => 'Shopping',
    'fas fa-money-bill' => 'Finance',
    'fas fa-star' => 'Important',
    'fas fa-lightbulb' => 'Ideas',
    'fas fa-chart-line' => 'Progress',
    'fas fa-trophy' => 'Goals',
    'fas fa-palette' => 'Creative'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Categories</title>
    <style>
        .category-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        .category-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-custom {
            background-color: #cdaf56;
            border-color: #cdaf56;
            color: white;
        }
        .btn-custom:hover {
            background-color: #b89c4a;
            border-color: #b89c4a;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Task Categories</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Category Form -->
        <div class="category-form">
            <h4><?php echo $editCategory ? 'Edit Category' : 'Add New Category'; ?></h4>
            <form method="POST" action="save_category.php" class="row g-3">
                <input type="hidden" name="id" value="<?php echo $editCategory ? $editCategory['id'] : ''; ?>">
                
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" required 
                           value="<?php echo $editCategory ? htmlspecialchars($editCategory['name']) : ''; ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Icon</label>
                    <select class="form-select" name="icon" required>
                        <?php foreach ($icons as $icon => $label): ?>
                        <option value="<?php echo $icon; ?>" <?php echo ($editCategory && $editCategory['icon'] == $icon) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Color</label>
                    <input type="color" class="form-control" name="color" 
                           value="<?php echo $editCategory ? $editCategory['color'] : '#cdaf56'; ?>" required>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-custom">
                        <?php echo $editCategory ? 'Update Category' : 'Add Category'; ?>
                    </button>
                    <?php if ($editCategory): ?>
                        <a href="categories.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Categories List -->
        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
            <div class="category-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3" style="color: <?php echo $category['color']; ?>">
                        <i class="<?php echo $category['icon']; ?> fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($category['name']); ?></h5>
                        <small class="text-muted">
                            <?php echo $category['task_count']; ?> task<?php echo $category['task_count'] != 1 ? 's' : ''; ?>
                        </small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-custom">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="delete_category.php" class="d-inline" 
                          onsubmit="return confirm('Are you sure you want to delete this category?');">
                        <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>