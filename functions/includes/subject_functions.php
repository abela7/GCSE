<?php
require_once 'db_connect.php';

/**
 * Get subject progress including topic completion and study time
 * @param mysqli $conn Database connection
 * @param int $subject_id Subject ID
 * @return array Subject progress details
 */
function get_subject_progress($conn, $subject_id) {
    try {
        // For Math (subject_id = 2), use math_topics and math_topic_progress
        // For English (subject_id = 1), use eng_topics and eng_topic_progress
        $table_prefix = ($subject_id == 2) ? 'math' : 'eng';
        
        // Get total topics count
        $topics_query = "SELECT COUNT(*) as total FROM {$table_prefix}_topics";
        $topics_result = $conn->query($topics_query);
        $total_topics = $topics_result->fetch_assoc()['total'];
        
        // Get completed topics count
        $completed_query = "SELECT COUNT(*) as completed 
                          FROM {$table_prefix}_topic_progress 
                          WHERE status = 'completed'";
        $completed_result = $conn->query($completed_query);
        $completed_topics = $completed_result->fetch_assoc()['completed'];
        
        // Calculate progress percentage
        $progress_percentage = ($total_topics > 0) ? ($completed_topics / $total_topics) * 100 : 0;
        
        // Get last studied date
        $last_studied_query = "SELECT MAX(last_studied) as last_studied 
                             FROM {$table_prefix}_topic_progress 
                             WHERE last_studied IS NOT NULL";
        $last_studied_result = $conn->query($last_studied_query);
        $last_studied = $last_studied_result->fetch_assoc()['last_studied'];
        
        return [
            'total_topics' => $total_topics,
            'completed_topics' => $completed_topics,
            'progress_percentage' => $progress_percentage,
            'last_studied' => $last_studied
        ];
    } catch (Exception $e) {
        error_log("Error in get_subject_progress: " . $e->getMessage());
        return [
            'total_topics' => 0,
            'completed_topics' => 0,
            'progress_percentage' => 0,
            'last_studied' => null
        ];
    }
}

/**
 * Get subject details
 * @param mysqli $conn Database connection
 * @param int $subject_id Subject ID
 * @return array|null Subject details or null if not found
 */
function get_subject_details($conn, $subject_id) {
    try {
        $query = "SELECT * FROM subjects WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error in get_subject_details: " . $e->getMessage());
        return null;
    }
} 