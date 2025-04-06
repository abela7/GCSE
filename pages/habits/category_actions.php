<?php
require_once '../../includes/db_connect.php';

header('Content-Type: application/json');

// Validate request
if (!isset($_REQUEST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $_REQUEST['action'];

// Get category details
if ($action === 'get') {
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'No category ID provided']);
        exit;
    }
    
    $id = intval($_GET['id']);
    $query = "SELECT * FROM habit_categories WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($category = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'category' => $category]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Category not found']);
    }
    exit;
}

// Create or update category
if ($action === 'create' || $action === 'update') {
    // Validate required fields
    $required_fields = ['name', 'color', 'icon'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }
    
    // Prepare data
    $name = trim($_POST['name']);
    $color = $_POST['color'];
    $icon = $_POST['icon'];
    
    // Get max display order for new categories
    if ($action === 'create') {
        $max_order_query = "SELECT COALESCE(MAX(display_order), 0) as max_order FROM habit_categories";
        $result = $conn->query($max_order_query);
        $row = $result->fetch_assoc();
        $display_order = $row['max_order'] + 1;
        
        // Create new category
        $query = "INSERT INTO habit_categories (name, color, icon, display_order) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sssi', $name, $color, $icon, $display_order);
    } else {
        // Update existing category
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'No category ID provided for update']);
            exit;
        }
        
        $id = intval($_POST['id']);
        $query = "UPDATE habit_categories SET name = ?, color = ?, icon = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sssi', $name, $color, $icon, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    exit;
}

// Delete category
if ($action === 'delete') {
    if (!isset($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'No category ID provided']);
        exit;
    }
    
    $id = intval($_POST['id']);
    
    // Check if category has habits
    $check_query = "SELECT COUNT(*) as habit_count FROM habits WHERE category_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['habit_count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete category with existing habits']);
        exit;
    }
    
    // Delete the category
    $query = "DELETE FROM habit_categories WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting category']);
    }
    exit;
}

// Invalid action
echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit; 