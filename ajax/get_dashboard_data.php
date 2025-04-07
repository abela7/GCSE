<?php
// Initialize session if needed for authentication
session_start();
header('Content-Type: application/json');

// Include database connection
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

// Initialize response array
$response = array();

// First, let's get table structure to understand what we're working with
$tables_info = array();
$table_names = array('assignments', 'tasks', 'habits', 'habit_tracking', 'study_sessions', 'exams');

foreach ($table_names as $table) {
    $structure_query = "DESCRIBE {$table}";
    try {
        $structure_result = $conn->query($structure_query);
        if ($structure_result) {
            while ($row = $structure_result->fetch_assoc()) {
                $tables_info[$table][] = $row['Field'];
            }
        }
    } catch (Exception $e) {
        // Table might not exist
        $tables_info[$table] = array('error' => 'Table might not exist');
    }
}

// Get assignments data
try {
    // Check if assignments table has the necessary fields
    if (isset($tables_info['assignments'])) {
        // Get assignment progress data
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

        // Get subject breakdown from assignments
        $subjects_query = "
            SELECT 
                subject,
                COUNT(*) as total_topics,
                SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed_topics,
                ROUND(AVG(confidence_level)) as avg_confidence
            FROM assignments
            GROUP BY subject
        ";
        $subjects_result = $conn->query($subjects_query);
        if ($subjects_result) {
            $response['subjects'] = array();
            while ($subject = $subjects_result->fetch_assoc()) {
                $response['subjects'][$subject['subject']] = array(
                    'total' => (int)$subject['total_topics'],
                    'completed' => (int)$subject['completed_topics'],
                    'confidence' => (int)$subject['avg_confidence'],
                    'progress' => $subject['total_topics'] > 0 ? 
                        round(($subject['completed_topics'] / $subject['total_topics']) * 100) : 0
                );
            }
        }

        // Get topic breakdown from assignments
        $topics_query = "
            SELECT 
                subject,
                topic,
                COUNT(*) as total_topics,
                SUM(CASE WHEN progress = 100 THEN 1 ELSE 0 END) as completed_topics
            FROM assignments
            GROUP BY subject, topic
            ORDER BY subject, topic
        ";
        $topics_result = $conn->query($topics_query);
        if ($topics_result) {
            $response['topics'] = array();
            while ($topic = $topics_result->fetch_assoc()) {
                $progress = $topic['total_topics'] > 0 ? 
                    round(($topic['completed_topics'] / $topic['total_topics']) * 100) : 0;
                
                $response['topics'][] = array(
                    'subject' => $topic['subject'],
                    'section' => $topic['topic'],
                    'total_topics' => (int)$topic['total_topics'],
                    'completed_topics' => (int)$topic['completed_topics'],
                    'progress' => $progress
                );
            }
        }
    }
} catch (Exception $e) {
    $response['errors']['assignments'] = $e->getMessage();
}

// Get tasks data
try {
    if (isset($tables_info['tasks'])) {
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
    }
} catch (Exception $e) {
    $response['errors']['tasks'] = $e->getMessage();
}

// Get habit data
try {
    if (isset($tables_info['habits']) && isset($tables_info['habit_tracking'])) {
        // Get habit completion counts
        $habits_query = "
            SELECT 
                h.id,
                h.name,
                COUNT(ht.id) as completion_count
            FROM habits h
            LEFT JOIN habit_tracking ht ON h.id = ht.habit_id AND ht.status = 'completed'
            WHERE h.is_active = 1
            GROUP BY h.id, h.name
            ORDER BY completion_count DESC, h.name ASC
            LIMIT 3
        ";
        
        $habits_result = $conn->query($habits_query);
        if ($habits_result) {
            $response['habits'] = array();
            while ($habit = $habits_result->fetch_assoc()) {
                $response['habits'][] = array(
                    'name' => $habit['name'],
                    'completion_count' => (int)$habit['completion_count']
                );
            }
        }

        // Get today's habit status
        $today_habits_query = "
            SELECT 
                COUNT(h.id) as total_habits,
                SUM(CASE WHEN ht.status = 'completed' THEN 1 ELSE 0 END) as completed_today,
                SUM(CASE WHEN ht.status IS NULL THEN 1 ELSE 0 END) as pending_today
            FROM habits h
            LEFT JOIN habit_tracking ht ON h.id = ht.habit_id AND DATE(ht.tracking_date) = CURRENT_DATE
            WHERE h.is_active = 1
        ";
        $today_habits_result = $conn->query($today_habits_query);
        if ($today_habits_result) {
            $response['habits_today'] = $today_habits_result->fetch_assoc();
            foreach ($response['habits_today'] as $key => $value) {
                $response['habits_today'][$key] = (int)$value;
            }
        }
    }
} catch (Exception $e) {
    $response['errors']['habits'] = $e->getMessage();
}

// Get study session data
try {
    if (isset($tables_info['study_sessions'])) {
        $study_query = "
            SELECT 
                SUM(duration) as total_duration,
                AVG(productivity_rating) as avg_productivity,
                COUNT(*) as total_sessions,
                MAX(session_date) as last_session_date
            FROM study_sessions
        ";
        $study_result = $conn->query($study_query);
        if ($study_result) {
            $study_data = $study_result->fetch_assoc();
            
            $last_date = $study_data['last_session_date'] ? new DateTime($study_data['last_session_date']) : null;
            $now = new DateTime();
            $days_since = $last_date ? $now->diff($last_date)->days : null;
            
            $response['study'] = array(
                'total_duration' => (int)$study_data['total_duration'],
                'total_hours' => $study_data['total_duration'] ? round($study_data['total_duration'] / 60, 1) : 0,
                'avg_productivity' => $study_data['avg_productivity'] ? round($study_data['avg_productivity'], 1) : 0,
                'total_sessions' => (int)$study_data['total_sessions'],
                'last_session_date' => $study_data['last_session_date'],
                'days_since_last' => $days_since
            );
        }
    }
} catch (Exception $e) {
    $response['errors']['study'] = $e->getMessage();
}

// Output the response as JSON
echo json_encode($response);

// Close the database connection
close_connection($conn);
?>
