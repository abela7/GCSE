<?php

function get_daily_words_with_status($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT w.*, 
                   CASE WHEN wp.word_id IS NOT NULL THEN 1 ELSE 0 END as is_practiced,
                   wp.practice_date,
                   (
                       SELECT COUNT(*)
                       FROM word_practice
                       WHERE word_id = w.id
                       AND user_id = ?
                   ) as practice_count
            FROM daily_words w
            LEFT JOIN word_practice wp ON w.id = wp.word_id 
                AND wp.user_id = ?
                AND wp.practice_date = CURRENT_DATE
            WHERE w.assigned_date = CURRENT_DATE
            ORDER BY w.word ASC
        ");
        
        $stmt->bind_param('ii', $user_id, $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in get_daily_words_with_status: " . $e->getMessage());
        return [];
    }
}

function mark_word_as_practiced($conn, $word_id, $user_id) {
    try {
        // First check if already practiced today
        $stmt = $conn->prepare("
            SELECT COUNT(*) as practiced
            FROM word_practice
            WHERE word_id = ?
            AND user_id = ?
            AND practice_date = CURRENT_DATE
        ");
        
        $stmt->bind_param('ii', $word_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['practiced'] > 0) {
            return true; // Already practiced today
        }
        
        // If not practiced, insert new practice record
        $stmt = $conn->prepare("
            INSERT INTO word_practice (word_id, user_id, practice_date)
            VALUES (?, ?, CURRENT_DATE)
        ");
        
        $stmt->bind_param('ii', $word_id, $user_id);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error in mark_word_as_practiced: " . $e->getMessage());
        return false;
    }
} 