<?php

function get_upcoming_exams_with_countdown($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            SELECT e.*, s.name as subject_name, s.color as subject_color,
                   DATEDIFF(exam_date, CURRENT_DATE) as days_remaining,
                   (
                       SELECT COUNT(DISTINCT topic_id)
                       FROM topic_completion
                       WHERE user_id = ?
                       AND subject_id = e.subject_id
                   ) as topics_completed,
                   (
                       SELECT COUNT(*)
                       FROM exam_topics et
                       WHERE et.exam_id = e.id
                   ) as total_topics
            FROM exams e
            JOIN subjects s ON e.subject_id = s.id
            WHERE e.user_id = ? 
            AND exam_date >= CURRENT_DATE
            ORDER BY exam_date ASC
            LIMIT 5
        ");
        
        $stmt->bind_param('ii', $user_id, $user_id);
        $stmt->execute();
        $exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Calculate readiness percentage for each exam
        foreach ($exams as &$exam) {
            $exam['readiness'] = $exam['total_topics'] > 0 
                ? round(($exam['topics_completed'] / $exam['total_topics']) * 100)
                : 0;
                
            // Add urgency level
            if ($exam['days_remaining'] <= 7) {
                $exam['urgency'] = 'high';
            } elseif ($exam['days_remaining'] <= 14) {
                $exam['urgency'] = 'medium';
            } else {
                $exam['urgency'] = 'low';
            }
        }
        
        return $exams;
    } catch (Exception $e) {
        error_log("Error in get_upcoming_exams_with_countdown: " . $e->getMessage());
        return [];
    }
}

function get_exam_topics($conn, $exam_id) {
    try {
        $stmt = $conn->prepare("
            SELECT t.*, 
                   CASE WHEN tc.completion_date IS NOT NULL THEN 1 ELSE 0 END as is_completed
            FROM exam_topics et
            JOIN topics t ON et.topic_id = t.id
            LEFT JOIN topic_completion tc ON t.id = tc.topic_id
            WHERE et.exam_id = ?
            ORDER BY t.name
        ");
        
        $stmt->bind_param('i', $exam_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in get_exam_topics: " . $e->getMessage());
        return [];
    }
} 