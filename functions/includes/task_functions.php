<?php
require_once 'db_connect.php';

/**
 * Get today's tasks with their details including category information
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array Array of tasks with their details
 */
function get_todays_tasks_with_details($conn, $user_id) {
    try {
        $today = date('Y-m-d');
        $query = "SELECT t.*, tc.name as category_name, tc.color as category_color 
                 FROM tasks t 
                 LEFT JOIN task_categories tc ON t.category_id = tc.id 
                 WHERE DATE(t.due_date) = ? 
                 AND t.is_active = 1 
                 ORDER BY t.due_time ASC, t.priority DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
        
        return $tasks;
    } catch (Exception $e) {
        error_log("Error in get_todays_tasks_with_details: " . $e->getMessage());
        return [];
    }
}

/**
 * Complete a task
 * @param mysqli $conn Database connection
 * @param int $task_id Task ID
 * @param int $user_id User ID
 * @return bool True if successful, false otherwise
 */
function complete_task($conn, $task_id, $user_id) {
    try {
        $query = "UPDATE tasks SET status = 'completed', completion_percentage = 100 WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $task_id);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error in complete_task: " . $e->getMessage());
        return false;
    }
}

/**
 * Add a new task
 * @param mysqli $conn Database connection
 * @param array $task_data Task data
 * @return bool|int Task ID if successful, false otherwise
 */
function add_task($conn, $task_data) {
    try {
        $query = "INSERT INTO tasks (category_id, title, priority, due_date, due_time, task_type) 
                 VALUES (?, ?, ?, CURDATE(), ?, 'one-time')";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isss", 
            $task_data['category_id'],
            $task_data['title'],
            $task_data['priority'],
            $task_data['due_time']
        );
        
        if ($stmt->execute()) {
            return $conn->insert_id;
        }
        return false;
    } catch (Exception $e) {
        error_log("Error in add_task: " . $e->getMessage());
        return false;
    }
} 