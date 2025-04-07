<?php
// Initialize session if needed for authentication
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Initialize response array
$response = array();

// Get task data
try {
    $tasks_query = "
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN status != 'completed' AND due_date < CURRENT_DATE THEN 1 ELSE 0 END) as overdue_tasks,
            SUM(CASE WHEN status != 'completed' AND due_date = CURRENT_DATE THEN 1 ELSE 0 END) as today_tasks,
            SUM(CASE WHEN status != 'completed' AND due_date > CURRENT_DATE THEN 1 ELSE 0 END) as upcoming_tasks
        FROM tasks
    ";
    $tasks_result = $conn->query($tasks_query);
    if ($tasks_result) {
        $response['tasks'] = $tasks_result->fetch_assoc();
        
        // Convert to integers
        foreach ($response['tasks'] as $key => $value) {
            $response['tasks'][$key] = (int)$value;
        }
    }
} catch (Exception $e) {
    $response['errors']['tasks'] = $e->getMessage();
}

// Get assignment data
try {
    $assignments_query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN progress > 0 AND progress < 100 THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN progress = 0 THEN 1 ELSE 0 END) as not_started,
            AVG(progress) as avg_progress
        FROM assignments
    ";
    $assignments_result = $conn->query($assignments_query);
    if ($assignments_result) {
        $response['assignments'] = $assignments_result->fetch_assoc();
        
        // Convert to proper types
        $response['assignments']['total'] = (int)$response['assignments']['total'];
        $response['assignments']['completed'] = (int)$response['assignments']['completed'];
        $response['assignments']['in_progress'] = (int)$response['assignments']['in_progress'];
        $response['assignments']['not_started'] = (int)$response['assignments']['not_started'];
        $response['assignments']['avg_progress'] = round((float)$response['assignments']['avg_progress'], 1);
    }
} catch (Exception $e) {
    $response['errors']['assignments'] = $e->getMessage();
}

// Get subject progress data
try {
    $subjects = array('Mathematics', 'English', 'Science');
    $response['subjects'] = array();
    
    foreach ($subjects as $subject) {
        $subject_query = "
            SELECT 
                COUNT(*) as total_topics,
                SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed_topics,
                ROUND(AVG(confidence_level)) as avg_confidence
            FROM assignments
            WHERE subject = ?
        ";
        $stmt = $conn->prepare($subject_query);
        $stmt->bind_param("s", $subject);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $subject_data = $result->fetch_assoc();
            $response['subjects'][$subject] = array(
                'total' => (int)$subject_data['total_topics'],
                'completed' => (int)$subject_data['completed_topics'],
                'confidence' => (int)$subject_data['avg_confidence'],
                'progress' => $subject_data['total_topics'] > 0 ? 
                    round(($subject_data['completed_topics'] / $subject_data['total_topics']) * 100) : 0
            );
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $response['errors']['subjects'] = $e->getMessage();
}

// Get habit streak data
try {
    $streak_query = "
        SELECT 
            h.id,
            h.name,
            COUNT(ht.id) as completion_count
        FROM habits h
        LEFT JOIN habit_tracking ht ON h.id = ht.habit_id AND ht.status = 'completed'
        WHERE h.is_active = 1
        GROUP BY h.id, h.name
        ORDER BY completion_count DESC
        LIMIT 3
    ";
    
    $streak_result = $conn->query($streak_query);
    if ($streak_result) {
        $response['habits'] = array();
        while ($habit = $streak_result->fetch_assoc()) {
            $response['habits'][] = array(
                'name' => $habit['name'],
                'completion_count' => (int)$habit['completion_count']
            );
        }
    }
} catch (Exception $e) {
    $response['errors']['habits'] = $e->getMessage();
}

// Get section breakdown data
try {
    $sections_query = "
        SELECT 
            subject,
            topic as section,
            COUNT(*) as total_topics,
            SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed_topics,
            ROUND((SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) / COUNT(*)) * 100) as progress
        FROM assignments
        GROUP BY subject, topic
        ORDER BY progress DESC
        LIMIT 5
    ";
    
    $sections_result = $conn->query($sections_query);
    if ($sections_result) {
        $response['sections'] = array();
        while ($section = $sections_result->fetch_assoc()) {
            $response['sections'][] = array(
                'subject' => $section['subject'],
                'section' => $section['section'],
                'total_topics' => (int)$section['total_topics'],
                'completed_topics' => (int)$section['completed_topics'],
                'progress' => (int)$section['progress']
            );
        }
    }
} catch (Exception $e) {
    $response['errors']['sections'] = $e->getMessage();
}

// Output the response as JSON
echo json_encode($response);

// Close the database connection
close_connection($conn);
?>
