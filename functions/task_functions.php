<?php

function get_todays_tasks_with_details($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT t.*, c.name as category_name, c.color as category_color,
                   CASE 
                       WHEN tc.completion_date IS NOT NULL THEN 'completed'
                       WHEN t.due_date < CURRENT_DATE THEN 'overdue'
                       ELSE 'pending'
                   END as status
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN task_completions tc ON t.id = tc.task_id 
                AND DATE(tc.completion_date) = CURRENT_DATE
            WHERE t.user_id = ? 
            AND (
                (t.is_recurring = 1 AND DAYOFWEEK(CURRENT_DATE) IN (
                    SELECT day_of_week FROM task_recurrence WHERE task_id = t.id
                ))
                OR 
                (t.is_recurring = 0 AND t.due_date = CURRENT_DATE)
            )
            ORDER BY t.due_time ASC
        ");
        
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in get_todays_tasks_with_details: " . $e->getMessage());
        return [];
    }
}

function complete_task($conn, $task_id, $user_id) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO task_completions (task_id, user_id, completion_date)
            VALUES (?, ?, NOW())
        ");
        
        $stmt->bind_param('ii', $task_id, $user_id);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error in complete_task: " . $e->getMessage());
        return false;
    }
} 