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

    // Get the habit ID from insert or from edit
    $habit_id = isset($_POST['id']) ? intval($_POST['id']) : $conn->insert_id;
    
    // Start a transaction for schedule updates
    $conn->begin_transaction();
    
    try {
        // Clear existing schedule entries
        $delete_schedule = $conn->prepare("DELETE FROM habit_schedule WHERE habit_id = ?");
        $delete_schedule->bind_param("i", $habit_id);
        $delete_schedule->execute();
        
        $delete_frequency = $conn->prepare("DELETE FROM habit_frequency WHERE habit_id = ?");
        $delete_frequency->bind_param("i", $habit_id);
        $delete_frequency->execute();
        
        // Process schedule data based on the selected type
        if (isset($_POST['schedule_type'])) {
            switch ($_POST['schedule_type']) {
                case 'specific_days':
                    if (isset($_POST['weekdays']) && is_array($_POST['weekdays'])) {
                        $insert_schedule = $conn->prepare("INSERT INTO habit_schedule (habit_id, day_of_week) VALUES (?, ?)");
                        
                        foreach ($_POST['weekdays'] as $day) {
                            $day = intval($day);
                            $insert_schedule->bind_param("ii", $habit_id, $day);
                            $insert_schedule->execute();
                        }
                    }
                    break;
                    
                case 'frequency':
                    if (isset($_POST['times_per_week']) && is_numeric($_POST['times_per_week'])) {
                        $times_per_week = min(7, max(1, intval($_POST['times_per_week'])));
                        $week_starts_on = isset($_POST['week_starts_on']) ? intval($_POST['week_starts_on']) : 0;
                        
                        $insert_frequency = $conn->prepare("INSERT INTO habit_frequency (habit_id, times_per_week, week_starts_on) VALUES (?, ?, ?)");
                        $insert_frequency->bind_param("iii", $habit_id, $times_per_week, $week_starts_on);
                        $insert_frequency->execute();
                    }
                    break;
                
                // 'daily' is the default - no schedule entries needed
                case 'daily':
                default:
                    // No additional data to save
                    break;
            }
        }
        
        // Commit the transaction
        $conn->commit();
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Error saving habit schedule: " . $e->getMessage());
        // Don't exit here, as we still want the original create/update to complete
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

// Get habit schedule
if ($action === 'get_schedule') {
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'No habit ID provided']);
        exit;
    }
    
    $id = intval($_GET['id']);
    
    // Check for specific days schedule
    $specific_days_query = "SELECT day_of_week FROM habit_schedule WHERE habit_id = ? ORDER BY day_of_week";
    $stmt = $conn->prepare($specific_days_query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $specific_days_result = $stmt->get_result();
    
    $specific_days = [];
    while ($day = $specific_days_result->fetch_assoc()) {
        $specific_days[] = (int)$day['day_of_week'];
    }
    
    // Check for frequency-based schedule
    $frequency_query = "SELECT times_per_week, week_starts_on FROM habit_frequency WHERE habit_id = ?";
    $stmt = $conn->prepare($frequency_query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $frequency_result = $stmt->get_result();
    $frequency = $frequency_result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'specific_days' => $specific_days,
        'frequency' => $frequency
    ]);
    exit;
}

// Invalid action
echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit; 