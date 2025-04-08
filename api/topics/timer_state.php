<?php
require_once '../../config/db_connect.php';

// Set timezone for PHP
date_default_timezone_set('Europe/London');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$topic_id = $data['topic_id'] ?? null;
$subject = $data['subject'] ?? null;
$action = $data['action'] ?? null;
$elapsed_seconds = isset($data['elapsed_seconds']) ? intval($data['elapsed_seconds']) : 0;

if (!$topic_id || !$subject || !$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    $db = new PDO("mysql:host=localhost;dbname=gcsedb", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Align MySQL timezone with PHP
    $db->exec("SET time_zone = '" . date('P') . "'");
    
    $db->beginTransaction();

    $study_time_table = $subject === 'english' ? 'eng_study_time_tracking' : 'study_time_tracking';
    $progress_table = $subject === 'english' ? 'eng_topic_progress' : 'topic_progress';

    switch ($action) {
        case 'get_state':
            $stmt = $db->prepare("
                SELECT *, UNIX_TIMESTAMP(start_time) as start_timestamp
                FROM $study_time_table 
                WHERE topic_id = ? 
                AND status IN ('active', 'paused')
                ORDER BY start_time DESC 
                LIMIT 1
            ");
            $stmt->execute([$topic_id]);
            $timer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($timer) {
                $elapsed = $timer['accumulated_seconds'];
                if ($timer['status'] === 'active') {
                    $elapsed += (time() - $timer['start_timestamp']);
                }
                
                echo json_encode([
                    'success' => true,
                    'timer' => [
                        'status' => $timer['status'],
                        'elapsed_seconds' => $elapsed
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'timer' => null
                ]);
            }
            break;

        case 'start':
            $stmt = $db->prepare("
                INSERT INTO $study_time_table 
                (topic_id, start_time, status, accumulated_seconds) 
                VALUES (?, NOW(), 'active', 0)
            ");
            $stmt->execute([$topic_id]);
            echo json_encode(['success' => true]);
            break;

        case 'pause':
            $stmt = $db->prepare("
                UPDATE $study_time_table 
                SET status = 'paused',
                    accumulated_seconds = ?
                WHERE topic_id = ? 
                AND status = 'active'
                ORDER BY start_time DESC 
                LIMIT 1
            ");
            $stmt->execute([$elapsed_seconds, $topic_id]);
            echo json_encode(['success' => true]);
            break;

        case 'resume':
            $stmt = $db->prepare("
                UPDATE $study_time_table 
                SET status = 'active',
                    start_time = NOW()
                WHERE topic_id = ? 
                AND status = 'paused'
                ORDER BY start_time DESC 
                LIMIT 1
            ");
            $stmt->execute([$topic_id]);
            echo json_encode(['success' => true]);
            break;

        case 'stop':
            // First check if the topic is already completed to avoid duplicate updates
            $check_stmt = $db->prepare("
                SELECT status FROM $progress_table 
                WHERE topic_id = ? AND status = 'completed'
            ");
            $check_stmt->execute([$topic_id]);
            $existing_progress = $check_stmt->fetch(PDO::FETCH_ASSOC);

            // Only update progress if not already completed
            if (!$existing_progress) {
                // Get total time spent so far
                $time_stmt = $db->prepare("
                    SELECT COALESCE(SUM(accumulated_seconds), 0) as total_time
                    FROM $study_time_table
                    WHERE topic_id = ? AND status = 'completed'
                ");
                $time_stmt->execute([$topic_id]);
                $total_time = $time_stmt->fetch(PDO::FETCH_ASSOC)['total_time'];

                // Add current session time
                $total_time += $elapsed_seconds;

                // Update progress table with completed status and accumulated time
                $progress_stmt = $db->prepare("
                    INSERT INTO $progress_table 
                    (topic_id, status, total_time_spent, last_studied, completion_date)
                    VALUES (?, 'completed', ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                    status = 'completed',
                    total_time_spent = VALUES(total_time_spent),
                    last_studied = NOW(),
                    completion_date = NOW()
                ");
                $progress_stmt->execute([$topic_id, $total_time]);
            }

            // Mark current study session as completed
            $stmt = $db->prepare("
                UPDATE $study_time_table 
                SET status = 'completed',
                    end_time = NOW(),
                    duration_seconds = ?,
                    accumulated_seconds = ?
                WHERE topic_id = ? 
                AND status IN ('active', 'paused')
                ORDER BY start_time DESC 
                LIMIT 1
            ");
            $stmt->execute([$elapsed_seconds, $elapsed_seconds, $topic_id]);

            // Update section progress if it's a math topic
            if ($subject !== 'english') {
                $section_stmt = $db->prepare("
                    UPDATE section_progress sp
                    JOIN math_sections ms ON sp.section_id = ms.id
                    JOIN math_subsections msub ON ms.id = msub.section_id
                    JOIN math_topics mt ON msub.id = mt.subsection_id
                    SET sp.completed_topics = (
                        SELECT COUNT(*)
                        FROM topic_progress tp2
                        JOIN math_topics mt2 ON tp2.topic_id = mt2.id
                        JOIN math_subsections msub2 ON mt2.subsection_id = msub2.id
                        WHERE msub2.section_id = ms.id
                        AND tp2.status = 'completed'
                    )
                    WHERE mt.id = ?
                ");
                $section_stmt->execute([$topic_id]);
            }
            
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }

    $db->commit();
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 