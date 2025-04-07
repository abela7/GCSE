<?php
require_once 'db_connect.php';

/**
 * Get daily vocabulary words with their practice status
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array Array of daily words with their status
 */
function get_daily_words_with_status($conn, $user_id) {
    try {
        $today = date('Y-m-d');
        $query = "SELECT w.*, 
                        CASE WHEN wp.practice_date = CURDATE() THEN 1 ELSE 0 END as practiced_today,
                        COUNT(wp.id) as total_practices
                 FROM vocabulary_words w
                 LEFT JOIN word_practice wp ON w.id = wp.word_id
                 WHERE w.assigned_date = ?
                 GROUP BY w.id
                 ORDER BY w.id ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $words = [];
        while ($row = $result->fetch_assoc()) {
            $words[] = $row;
        }
        
        return $words;
    } catch (Exception $e) {
        error_log("Error in get_daily_words_with_status: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark a word as practiced for today
 * @param mysqli $conn Database connection
 * @param int $word_id Word ID
 * @param int $user_id User ID
 * @return bool True if successful, false otherwise
 */
function mark_word_as_practiced($conn, $word_id, $user_id) {
    try {
        // Check if already practiced today
        $check_query = "SELECT id FROM word_practice 
                       WHERE word_id = ? AND practice_date = CURDATE()";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $word_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            return true; // Already practiced today
        }
        
        // Add new practice record
        $insert_query = "INSERT INTO word_practice (word_id, practice_date) VALUES (?, CURDATE())";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("i", $word_id);
        
        return $insert_stmt->execute();
    } catch (Exception $e) {
        error_log("Error in mark_word_as_practiced: " . $e->getMessage());
        return false;
    }
} 