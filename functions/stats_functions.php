<?php

/**
 * Get daily statistics for practice items
 * @param PDO $conn Database connection
 * @return array Statistics array
 */
function get_daily_stats($conn) {
    $stats = [
        'total_items' => 0,
        'favorite_items' => 0,
        'items_added_today' => 0,
        'categories_practiced' => 0
    ];
    
    try {
        $query = "
            SELECT 
                COUNT(DISTINCT pi.id) as total_items,
                COUNT(DISTINCT CASE WHEN fpi.practice_item_id IS NOT NULL THEN pi.id END) as favorite_items,
                COUNT(DISTINCT CASE WHEN DATE(pi.created_at) = CURDATE() THEN pi.id END) as items_added_today,
                (
                    SELECT COUNT(DISTINCT category_id) 
                    FROM practice_items 
                    WHERE DATE(created_at) = CURDATE()
                ) as categories_practiced
            FROM practice_items pi
            LEFT JOIN favorite_practice_items fpi ON pi.id = fpi.practice_item_id
        ";
        
        $result = $conn->query($query);
        if ($result) {
            $stats = $result->fetch_assoc();
        }
    } catch (Exception $e) {
        error_log("Error getting daily stats: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Get upcoming assignments with progress
 * @param PDO $conn Database connection
 * @param int $limit Number of assignments to return
 * @return array Assignments array
 */
function get_upcoming_assignments($conn, $limit = 3) {
    $assignments = [];
    
    try {
        $query = "
            SELECT 
                a.*, 
                COUNT(ac.id) as total_criteria,
                SUM(CASE WHEN acp.status = 'completed' THEN 1 ELSE 0 END) as completed_criteria
            FROM access_assignments a
            LEFT JOIN assessment_criteria ac ON a.id = ac.assignment_id
            LEFT JOIN assignment_criteria_progress acp ON ac.id = acp.criteria_id
            WHERE a.due_date >= CURDATE()
            GROUP BY a.id
            ORDER BY a.due_date ASC
            LIMIT ?
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error getting upcoming assignments: " . $e->getMessage());
    }
    
    return $assignments;
}

/**
 * Get recent practice items
 * @param PDO $conn Database connection
 * @param int $limit Number of items to return
 * @return array Practice items array
 */
function get_recent_practice_items($conn, $limit = 5) {
    $items = [];
    
    try {
        $query = "
            SELECT pi.*, pc.name as category_name,
                   CASE WHEN fpi.practice_item_id IS NOT NULL THEN 1 ELSE 0 END as is_favorite
            FROM practice_items pi
            JOIN practice_categories pc ON pi.category_id = pc.id
            LEFT JOIN favorite_practice_items fpi ON pi.id = fpi.practice_item_id
            ORDER BY pi.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error getting recent practice items: " . $e->getMessage());
    }
    
    return $items;
}

/**
 * Get today's habits with their status
 * @param mysqli $conn Database connection
 * @return array Habits array
 */
function get_todays_habits($conn) {
    $habits = [];
    
    try {
        $query = "
            SELECT 
                h.*,
                c.name as category_name,
                c.icon as category_icon,
                c.color as category_color,
                COALESCE(hp.status, 'pending') as today_status,
                hp.notes
            FROM habits h
            JOIN categories c ON h.category_id = c.id
            LEFT JOIN habit_progress hp ON h.id = hp.habit_id 
                AND hp.date = CURDATE()
            WHERE h.is_active = 1
            ORDER BY h.target_time ASC
        ";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $habits[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error getting today's habits: " . $e->getMessage());
    }
    
    return $habits;
}

/**
 * Get today's tasks
 * @param mysqli $conn Database connection
 * @return array Tasks array
 */
function get_todays_tasks($conn) {
    $tasks = [];
    
    try {
        $query = "
            SELECT t.*, c.name as category_name, c.icon as category_icon, c.color as category_color,
                   COALESCE(ti.status, t.status) as effective_status
            FROM tasks t 
            JOIN categories c ON t.category_id = c.id 
            LEFT JOIN task_instances ti ON t.id = ti.task_id AND ti.due_date = CURDATE()
            WHERE (t.task_type = 'one-time' AND t.due_date = CURDATE() AND t.status != 'completed')
               OR (t.task_type = 'recurring' AND ti.id IS NOT NULL AND ti.status != 'completed')
            ORDER BY t.priority DESC, t.due_date ASC
        ";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error getting today's tasks: " . $e->getMessage());
    }
    
    return $tasks;
}

/**
 * Get upcoming exams
 * @param mysqli $conn Database connection
 * @param int $days Number of days to look ahead
 * @return array Exams array
 */
function get_upcoming_exams($conn, $days = 30) {
    $exams = [];
    
    try {
        $query = "
            SELECT e.*, s.name as subject_name, s.color as subject_color,
                   DATEDIFF(e.exam_date, CURRENT_DATE) as days_remaining
            FROM exams e 
            JOIN subjects s ON e.subject_id = s.id 
            WHERE e.exam_date > CURRENT_DATE 
              AND e.exam_date <= DATE_ADD(CURRENT_DATE, INTERVAL ? DAY)
            ORDER BY e.exam_date ASC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $exams[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error getting upcoming exams: " . $e->getMessage());
    }
    
    return $exams;
}

/**
 * Get exam reports for today
 * @param mysqli $conn Database connection
 * @return array Exam reports array
 */
function get_todays_exam_reports($conn) {
    $reports = [];
    
    try {
        $query = "
            SELECT er.*, e.title as exam_title, s.name as subject_name, s.color as subject_color
            FROM exam_reports er
            JOIN exams e ON er.exam_id = e.id
            JOIN subjects s ON e.subject_id = s.id
            WHERE DATE(er.date) = CURDATE()
            ORDER BY er.date DESC
        ";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error getting today's exam reports: " . $e->getMessage());
    }
    
    return $reports;
} 