<?php
require_once 'db_connect.php';

/**
 * Get upcoming exams with countdown information
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array Array of exams with countdown details
 */
function get_upcoming_exams_with_countdown($conn, $user_id) {
    try {
        $query = "SELECT e.*, s.name as subject_name, s.color as subject_color
                 FROM exams e
                 LEFT JOIN subjects s ON e.subject_id = s.id
                 WHERE e.exam_date >= CURDATE()
                 ORDER BY e.exam_date ASC
                 LIMIT 3";
        
        $result = $conn->query($query);
        $exams = [];
        
        while ($row = $result->fetch_assoc()) {
            $exam_date = new DateTime($row['exam_date']);
            $today = new DateTime();
            $interval = $today->diff($exam_date);
            $days_remaining = $interval->days;
            
            // Calculate urgency color based on days remaining
            if ($days_remaining <= 7) {
                $urgency_color = '#dc3545'; // Red for urgent
            } elseif ($days_remaining <= 14) {
                $urgency_color = '#ffc107'; // Yellow for warning
            } else {
                $urgency_color = '#28a745'; // Green for okay
            }
            
            // Calculate progress percentage (inverse of days remaining)
            $progress = min(100, max(0, (30 - $days_remaining) * (100/30)));
            
            $row['days_remaining'] = $days_remaining;
            $row['urgency_color'] = $urgency_color;
            $row['progress'] = $progress;
            
            $exams[] = $row;
        }
        
        return $exams;
    } catch (Exception $e) {
        error_log("Error in get_upcoming_exams_with_countdown: " . $e->getMessage());
        return [];
    }
}

/**
 * Get exam topics and their completion status
 * @param mysqli $conn Database connection
 * @param int $exam_id Exam ID
 * @return array Array of topics and their status
 */
function get_exam_topics($conn, $exam_id) {
    try {
        $query = "SELECT t.*, tp.status, tp.completion_date
                 FROM topics t
                 LEFT JOIN topic_progress tp ON t.id = tp.topic_id
                 WHERE t.exam_id = ?
                 ORDER BY t.display_order ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $topics = [];
        while ($row = $result->fetch_assoc()) {
            $topics[] = $row;
        }
        
        return $topics;
    } catch (Exception $e) {
        error_log("Error in get_exam_topics: " . $e->getMessage());
        return [];
    }
}