<?php

function get_todays_habits_with_progress($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT h.*, c.name as category_name, c.color as category_color,
                   CASE 
                       WHEN hc.completion_date IS NOT NULL THEN 'completed'
                       WHEN h.target_time < CURRENT_TIME THEN 'missed'
                       ELSE 'pending'
                   END as today_status,
                   (
                       SELECT COUNT(*) 
                       FROM habit_completions 
                       WHERE habit_id = h.id 
                       AND completion_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                   ) as weekly_completions
            FROM habits h
            LEFT JOIN categories c ON h.category_id = c.id
            LEFT JOIN habit_completions hc ON h.id = hc.habit_id 
                AND DATE(hc.completion_date) = CURRENT_DATE
            WHERE h.user_id = ? AND h.is_active = 1
            ORDER BY h.target_time ASC
        ");
        
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in get_todays_habits_with_progress: " . $e->getMessage());
        return [];
    }
}

function complete_habit($conn, $habit_id, $user_id) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO habit_completions (habit_id, user_id, completion_date, points_earned)
            VALUES (?, ?, NOW(), 
                CASE 
                    WHEN CURRENT_TIME <= (
                        SELECT target_time FROM habits WHERE id = ?
                    ) THEN 10
                    ELSE 5
                END
            )
        ");
        
        $stmt->bind_param('iii', $habit_id, $user_id, $habit_id);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error in complete_habit: " . $e->getMessage());
        return false;
    }
} 