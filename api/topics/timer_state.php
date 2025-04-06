<?php
require_once '../../config/database.php';

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
    $db = getDBConnection();
    
    // Align MySQL timezone with PHP
    $db->exec("SET time_zone = '" . date('P') . "'");
    
    $db->beginTransaction();

    $study_time_table = $subject === 'english' ? 'eng_study_time_tracking' : 'study_time_tracking';

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
            // Update progress table
            $progress_table = $subject === 'english' ? 'eng_topic_progress' : 'topic_progress';
            $study_time_table = $subject === 'english' ? 'eng_study_time_tracking' : 'study_time_tracking';
            
            // Update progress table with completed status and time spent (in seconds)
            $stmt = $db->prepare("
                INSERT INTO $progress_table (topic_id, total_time_spent, last_studied, status)
                VALUES (?, ?, NOW(), 'completed')
                ON DUPLICATE KEY UPDATE
                total_time_spent = total_time_spent + ?,
                last_studied = NOW(),
                status = 'completed'
            ");
            $stmt->execute([$topic_id, $elapsed_seconds, $elapsed_seconds]);

            // Mark study session as completed
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
            
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }

    $db->commit();
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 