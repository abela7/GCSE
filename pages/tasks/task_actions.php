<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../../includes/db_connect.php';
require_once 'task_functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'get_task':
                if (isset($_GET['task_id'])) {
                    $taskId = (int)$_GET['task_id'];
                    $stmt = $conn->prepare("
                        SELECT t.*, c.icon as category_icon 
                        FROM tasks t
                        JOIN task_categories c ON t.category_id = c.id
                        WHERE t.id = ?
                    ");
                    $stmt->bind_param('i', $taskId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $task = $result->fetch_assoc();
                    
                    if ($task) {
                        echo json_encode($task);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Task not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Task ID not provided']);
                }
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    }
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_task':
                echo json_encode(saveTask($conn, $_POST));
                break;
                
            case 'update_task':
                echo json_encode(updateTask($conn, $_POST));
                break;
                
            case 'delete_task':
                if (isset($_POST['task_id'])) {
                    echo json_encode(deleteTask($conn, $_POST['task_id']));
                } else {
                    echo json_encode(['success' => false, 'message' => 'Task ID not provided']);
                }
                break;
                
            case 'toggle_status':
                if (isset($_POST['task_id']) && isset($_POST['activate'])) {
                    echo json_encode(toggleTaskStatus($conn, $_POST['task_id'], $_POST['activate']));
                } else {
                    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                }
                break;
                
            case 'update_task_status':
                if (isset($_POST['task_id']) && isset($_POST['status'])) {
                    $taskId = (int)$_POST['task_id'];
                    $status = $_POST['status'];
                    
                    try {
                        $conn->begin_transaction();
                        
                        // Get task details first
                        $stmt = $conn->prepare("SELECT task_type FROM tasks WHERE id = ?");
                        $stmt->bind_param('i', $taskId);
                        $stmt->execute();
                        $task = $stmt->get_result()->fetch_assoc();
                        
                        if (!$task) {
                            throw new Exception("Task not found");
                        }
                        
                        if ($task['task_type'] === 'one-time') {
                            // For one-time tasks, update the task directly
                            $stmt = $conn->prepare("UPDATE tasks 
                                                  SET status = ?, 
                                                      is_active = CASE WHEN ? IN ('completed', 'not_done') THEN 0 ELSE 1 END,
                                                      updated_at = CURRENT_TIMESTAMP 
                                                  WHERE id = ?");
                            $stmt->bind_param('ssi', $status, $status, $taskId);
                            $stmt->execute();
                        } else {
                            // For recurring tasks, update the current instance
                            $stmt = $conn->prepare("UPDATE task_instances 
                                                  SET status = ?,
                                                      updated_at = CURRENT_TIMESTAMP 
                                                  WHERE task_id = ? 
                                                  AND due_date = CURRENT_DATE 
                                                  AND status IN ('pending', 'snoozed')");
                            $stmt->bind_param('si', $status, $taskId);
                            $stmt->execute();
                            
                            // If the task is completed or cancelled, also update the task's status
                            if ($status === 'completed' || $status === 'not_done') {
                                $stmt = $conn->prepare("UPDATE tasks 
                                                      SET status = ?,
                                                          updated_at = CURRENT_TIMESTAMP 
                                                      WHERE id = ?");
                                $stmt->bind_param('si', $status, $taskId);
                                $stmt->execute();
                            }
                        }
                        
                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => 'Task status updated successfully']);
                    } catch (Exception $e) {
                        $conn->rollback();
                        echo json_encode(['success' => false, 'message' => 'Error updating task status: ' . $e->getMessage()]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Missing task_id or status parameter']);
                }
                break;
                
            case 'snooze_task':
                if (isset($_POST['task_id']) && isset($_POST['minutes'])) {
                    $taskId = (int)$_POST['task_id'];
                    $minutes = (int)$_POST['minutes'];
                    
                    try {
                        $conn->begin_transaction();
                        
                        // Get task details first
                        $stmt = $conn->prepare("
                            SELECT t.*, ti.id as instance_id 
                            FROM tasks t 
                            LEFT JOIN task_instances ti ON t.id = ti.task_id 
                                AND ti.due_date = CURRENT_DATE 
                                AND ti.status IN ('pending', 'snoozed')
                            WHERE t.id = ?
                        ");
                        $stmt->bind_param('i', $taskId);
                        $stmt->execute();
                        $task = $stmt->get_result()->fetch_assoc();
                        
                        if (!$task) {
                            throw new Exception("Task not found");
                        }
                        
                        // Calculate new due time
                        $dueDateTime = new DateTime($task['due_date'] . ' ' . $task['due_time']);
                        $now = new DateTime();
                        
                        // If task is overdue, start from current time
                        if ($dueDateTime < $now) {
                            $dueDateTime = $now;
                        }
                        
                        // Add snooze minutes
                        $dueDateTime->add(new DateInterval("PT{$minutes}M"));
                        
                        $newDate = $dueDateTime->format('Y-m-d');
                        $newTime = $dueDateTime->format('H:i:s');
                        
                        if ($task['task_type'] === 'one-time') {
                            // For one-time tasks, update the task directly
                            $stmt = $conn->prepare("
                                UPDATE tasks 
                                SET due_date = ?,
                                    due_time = ?,
                                    status = 'snoozed',
                                    updated_at = CURRENT_TIMESTAMP 
                                WHERE id = ?
                            ");
                            $stmt->bind_param('ssi', $newDate, $newTime, $taskId);
                            $stmt->execute();
                            
                            if ($stmt->affected_rows === 0) {
                                throw new Exception("Failed to update task");
                            }
                        } else {
                            // For recurring tasks, handle both tables
                            
                            // Update the main task's time (this affects future instances)
                            $stmt = $conn->prepare("
                                UPDATE tasks 
                                SET due_time = ?,
                                    updated_at = CURRENT_TIMESTAMP 
                                WHERE id = ?
                            ");
                            $stmt->bind_param('si', $newTime, $taskId);
                            $stmt->execute();
                            
                            if ($task['instance_id']) {
                                // Update existing instance
                                $stmt = $conn->prepare("
                                    UPDATE task_instances 
                                    SET due_time = ?,
                                        status = 'snoozed',
                                        updated_at = CURRENT_TIMESTAMP 
                                    WHERE id = ?
                                ");
                                $stmt->bind_param('si', $newTime, $task['instance_id']);
                                $stmt->execute();
                            } else {
                                // Create new instance for today
                                $stmt = $conn->prepare("
                                    INSERT INTO task_instances 
                                    (task_id, due_date, due_time, status, created_at, updated_at)
                                    VALUES (?, CURRENT_DATE, ?, 'snoozed', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                                ");
                                $stmt->bind_param('is', $taskId, $newTime);
                                $stmt->execute();
                            }
                        }
                        
                        $conn->commit();
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Task snoozed successfully',
                            'new_due_date' => $newDate,
                            'new_due_time' => $newTime
                        ]);
                    } catch (Exception $e) {
                        $conn->rollback();
                        echo json_encode(['success' => false, 'message' => 'Error snoozing task: ' . $e->getMessage()]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Missing task_id or minutes parameter']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No action specified']);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 