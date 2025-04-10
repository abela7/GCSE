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
        // Get scheduled days if any
        $scheduled_days_query = "SELECT day_of_week FROM habit_schedule WHERE habit_id = ? ORDER BY day_of_week";
        $scheduled_days_stmt = $conn->prepare($scheduled_days_query);
        $scheduled_days_stmt->bind_param('i', $id);
        $scheduled_days_stmt->execute();
        $scheduled_days_result = $scheduled_days_stmt->get_result();
        
        $scheduled_days = [];
        while ($day = $scheduled_days_result->fetch_assoc()) {
            $scheduled_days[] = (int) $day['day_of_week'];
        }
        
        if (!empty($scheduled_days)) {
            $habit['scheduled_days'] = $scheduled_days;
        }
        
        // Get frequency information if any
        $frequency_query = "SELECT times_per_week, week_starts_on FROM habit_frequency WHERE habit_id = ?";
        $frequency_stmt = $conn->prepare($frequency_query);
        $frequency_stmt->bind_param('i', $id);
        $frequency_stmt->execute();
        $frequency_result = $frequency_stmt->get_result();
        
        if ($frequency = $frequency_result->fetch_assoc()) {
            $habit['times_per_week'] = (int) $frequency['times_per_week'];
            $habit['week_starts_on'] = (int) $frequency['week_starts_on'];
        }
        
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
    $schedule_type = isset($_POST['schedule_type']) ? $_POST['schedule_type'] : 'daily';
    
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
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        if ($action === 'create') {
            // Create new habit
            $query = "INSERT INTO habits (name, category_id, point_rule_id, target_time, description, is_active) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('siissi', $name, $category_id, $point_rule_id, $target_time, $description, $is_active);
            
            if (!$stmt->execute()) {
                throw new Exception('Error creating habit: ' . $stmt->error);
            }
            
            // Get the newly inserted habit's ID
            $habit_id = $conn->insert_id;
        } else {
            // Update existing habit
            if (!isset($_POST['id'])) {
                throw new Exception('No habit ID provided for update');
            }
            
            $habit_id = intval($_POST['id']);
            $query = "UPDATE habits 
                      SET name = ?, category_id = ?, point_rule_id = ?, target_time = ?, 
                          description = ?, is_active = ?
                      WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('siissii', $name, $category_id, $point_rule_id, $target_time, $description, $is_active, $habit_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Error updating habit: ' . $stmt->error);
            }
        }
        
        // Clear existing schedule entries for both tables
        $delete_schedule = $conn->prepare("DELETE FROM habit_schedule WHERE habit_id = ?");
        $delete_schedule->bind_param('i', $habit_id);
        if (!$delete_schedule->execute()) {
            throw new Exception('Error clearing schedule: ' . $delete_schedule->error);
        }
        
        $delete_frequency = $conn->prepare("DELETE FROM habit_frequency WHERE habit_id = ?");
        $delete_frequency->bind_param('i', $habit_id);
        if (!$delete_frequency->execute()) {
            throw new Exception('Error clearing frequency: ' . $delete_frequency->error);
        }
        
        // Process based on schedule type
        if ($schedule_type === 'specific_days') {
            if (isset($_POST['weekdays']) && is_array($_POST['weekdays'])) {
                $insert_schedule = $conn->prepare("INSERT INTO habit_schedule (habit_id, day_of_week) VALUES (?, ?)");
                
                foreach ($_POST['weekdays'] as $day) {
                    $day_val = intval($day);
                    $insert_schedule->bind_param('ii', $habit_id, $day_val);
                    if (!$insert_schedule->execute()) {
                        throw new Exception('Error setting schedule days: ' . $insert_schedule->error);
                    }
                }
            }
        } else if ($schedule_type === 'frequency') {
            if (isset($_POST['times_per_week']) && is_numeric($_POST['times_per_week'])) {
                $times_per_week = min(7, max(1, intval($_POST['times_per_week'])));
                $week_starts_on = isset($_POST['week_starts_on']) ? intval($_POST['week_starts_on']) : 0;
                
                $insert_frequency = $conn->prepare("INSERT INTO habit_frequency (habit_id, times_per_week, week_starts_on) VALUES (?, ?, ?)");
                $insert_frequency->bind_param('iii', $habit_id, $times_per_week, $week_starts_on);
                if (!$insert_frequency->execute()) {
                    throw new Exception('Error setting frequency: ' . $insert_frequency->error);
                }
            }
        }
        // For daily habits, no entries are needed in either table
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'id' => $habit_id]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
        
        // Delete schedule records
        $delete_schedule = "DELETE FROM habit_schedule WHERE habit_id = ?";
        $stmt = $conn->prepare($delete_schedule);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        // Delete frequency records
        $delete_frequency = "DELETE FROM habit_frequency WHERE habit_id = ?";
        $stmt = $conn->prepare($delete_frequency);
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