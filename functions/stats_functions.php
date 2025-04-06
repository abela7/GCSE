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