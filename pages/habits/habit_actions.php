<?php
require_once '../../includes/db_connect.php';

// Set timezone to London
date_default_timezone_set('Europe/London');

header('Content-Type: application/json');

// Validate request
if (!isset($_REQUEST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $_REQUEST['action'];

// Get habit details
if ($action === 'get') {
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'No habit ID provided']);
        exit;
    }
    
    $id = intval($_GET['id']);
    $query = "SELECT h.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
              pr.name as point_rule_name, pr.completion_points
              FROM habits h
              LEFT JOIN habit_categories c ON h.category_id = c.id
              LEFT JOIN habit_point_rules pr ON h.point_rule_id = pr.id
              WHERE h.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($habit = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'habit' => $habit]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Habit not found']);
    }
    exit;
}

// Create or update habit
if ($action === 'create' || $action === 'update') {
    // Validate required fields
    $required_fields = ['name', 'category_id', 'point_rule_id', 'target_time'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }
    
    // Prepare data
    $name = trim($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $point_rule_id = intval($_POST['point_rule_id']);
    $target_time = $_POST['target_time'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate category and point rule exist
    $validation_query = "SELECT 
        (SELECT COUNT(*) FROM habit_categories WHERE id = ?) as category_exists,
        (SELECT COUNT(*) FROM habit_point_rules WHERE id = ?) as rule_exists";
    $stmt = $conn->prepare($validation_query);
    $stmt->bind_param('ii', $category_id, $point_rule_id);
    $stmt->execute();
    $validation_result = $stmt->get_result()->fetch_assoc();
    
    if (!$validation_result['category_exists']) {
        echo json_encode(['success' => false, 'message' => 'Invalid category']);
        exit;
    }
    if (!$validation_result['rule_exists']) {
        echo json_encode(['success' => false, 'message' => 'Invalid point rule']);
        exit;
    }
    
    if ($action === 'create') {
        // Create new habit
        $query = "INSERT INTO habits (name, category_id, point_rule_id, target_time, description, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('siissi', $name, $category_id, $point_rule_id, $target_time, $description, $is_active);
        
        if ($stmt->execute()) {
            // Get the newly inserted habit's ID
            $new_habit_id = $conn->insert_id;
            echo json_encode(['success' => true, 'id' => $new_habit_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
    } else {
        // Update existing habit
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'No habit ID provided for update']);
            exit;
        }
        
        $id = intval($_POST['id']);
        $query = "UPDATE habits 
                  SET name = ?, category_id = ?, point_rule_id = ?, target_time = ?, 
                      description = ?, is_active = ?
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('siissii', $name, $category_id, $point_rule_id, $target_time, $description, $is_active, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
    }
    exit;
}

// Delete habit
if ($action === 'delete') {
    if (!isset($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'No habit ID provided']);
        exit;
    }
    
    $id = intval($_POST['id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete related records first
        $delete_completions = "DELETE FROM habit_completions WHERE habit_id = ?";
        $stmt = $conn->prepare($delete_completions);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        // Delete the habit
        $delete_habit = "DELETE FROM habits WHERE id = ?";
        $stmt = $conn->prepare($delete_habit);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error deleting habit: ' . $e->getMessage()]);
    }
    exit;
}

// Toggle habit active status
if ($action === 'toggle_active') {
    if (!isset($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'No habit ID provided']);
        exit;
    }
    
    $id = intval($_POST['id']);
    $query = "UPDATE habits SET is_active = NOT is_active WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error toggling habit status']);
    }
    exit;
}

// Invalid action
echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit; 