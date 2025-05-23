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
                    
                    // Map incoming status if needed
                    if ($status === 'done') {
                        $status = 'completed';
                    } elseif (in_array($status, ['cancel', 'not done', 'not-done'])) {
                        $status = 'not_done';
                    }
                    
                    // Debug logging
                    error_log("Received status update request - Task ID: $taskId, Original Status: " . $_POST['status'] . ", Mapped Status: $status");
                    
                    // Validate status value
                    $validStatuses = ['pending', 'in_progress', 'completed', 'not_done', 'snoozed'];
                    if (!in_array($status, $validStatuses)) {
                        error_log("Invalid status value received: '$status'. Valid statuses are: " . implode(', ', $validStatuses));
                        echo json_encode(['success' => false, 'message' => "Invalid status value: '$status'"]);
                        exit;
                    }
                    
                    try {
                        $conn->begin_transaction();
                        
                        // Get task details first
                        $stmt = $conn->prepare("SELECT task_type, status as current_status FROM tasks WHERE id = ?");
                        $stmt->bind_param('i', $taskId);
                        $stmt->execute();
                        $task = $stmt->get_result()->fetch_assoc();
                        
                        if (!$task) {
                            throw new Exception("Task not found");
                        }
                        
                        // Log current state
                        error_log("Updating task {$taskId} from status '{$task['current_status']}' to '{$status}'");
                        
                        if ($task['task_type'] === 'one-time') {
                            // For one-time tasks, update the task directly
                            $stmt = $conn->prepare("UPDATE tasks 
                                                  SET status = ?, 
                                                      updated_at = CURRENT_TIMESTAMP
                                                  WHERE id = ?");
                            $stmt->bind_param('si', $status, $taskId);
                            if (!$stmt->execute()) {
                                throw new Exception("Failed to update task status: " . $stmt->error);
                            }
                            
                            // Log the attempted update
                            error_log("Attempting to update task {$taskId} to status: {$status}");
                            
                            // Verify the update with detailed error
                            $stmt = $conn->prepare("SELECT status FROM tasks WHERE id = ?");
                            $stmt->bind_param('i', $taskId);
                            $stmt->execute();
                            $result = $stmt->get_result()->fetch_assoc();
                            
                            if (!$result) {
                                throw new Exception("Task not found after update");
                            }
                            
                            // Log the actual status after update
                            error_log("Task {$taskId} status after update: " . ($result['status'] ?? 'null'));
                            
                            if ($result['status'] !== $status) {
                                throw new Exception("Status update verification failed. Got: " . ($result['status'] ?? 'null'));
                            }
                        } else {
                            // For recurring tasks, update both the task and instance
                            // First, check if there's an instance for today
                            $stmt = $conn->prepare("SELECT id, status FROM task_instances 
                                                  WHERE task_id = ? AND due_date = CURRENT_DATE");
                            $stmt->bind_param('i', $taskId);
                            $stmt->execute();
                            $instance = $stmt->get_result()->fetch_assoc();
                            
                            if ($instance) {
                                // Update existing instance
                                $stmt = $conn->prepare("UPDATE task_instances 
                                                      SET status = ?,
                                                          updated_at = CURRENT_TIMESTAMP 
                                                      WHERE id = ?");
                                $stmt->bind_param('si', $status, $instance['id']);
                                if (!$stmt->execute()) {
                                    throw new Exception("Failed to update task instance: " . $stmt->error);
                                }
                            } else {
                                // Create new instance for today
                                $stmt = $conn->prepare("INSERT INTO task_instances 
                                                      (task_id, due_date, status, created_at, updated_at)
                                                      VALUES (?, CURRENT_DATE, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                                $stmt->bind_param('is', $taskId, $status);
                                if (!$stmt->execute()) {
                                    throw new Exception("Failed to create task instance: " . $stmt->error);
                                }
                            }
                            
                            // For recurring tasks, keep the main task active but update its status
                            $stmt = $conn->prepare("UPDATE tasks 
                                                  SET status = ?,
                                                      updated_at = CURRENT_TIMESTAMP
                                                  WHERE id = ?");
                            $stmt->bind_param('si', $status, $taskId);
                            if (!$stmt->execute()) {
                                throw new Exception("Failed to update main task: " . $stmt->error);
                            }
                            
                            // Verify the updates
                            $stmt = $conn->prepare("SELECT t.status as task_status, ti.status as instance_status 
                                                  FROM tasks t 
                                                  LEFT JOIN task_instances ti ON t.id = ti.task_id 
                                                  AND ti.due_date = CURRENT_DATE
                                                  WHERE t.id = ?");
                            $stmt->bind_param('i', $taskId);
                            $stmt->execute();
                            $result = $stmt->get_result()->fetch_assoc();
                            
                            if (!$result) {
                                throw new Exception("Could not verify task update - task not found");
                            }
                            
                            // For recurring tasks, we mainly care about the instance status
                            if ($instance && (!isset($result['instance_status']) || $result['instance_status'] !== $status)) {
                                throw new Exception("Status update verification failed for recurring task instance. Expected: {$status}, Got: " . ($result['instance_status'] ?? 'null'));
                            }
                        }
                        
                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => 'Task status updated successfully']);
                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        error_log("Error updating task status: " . $e->getMessage());
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
                                AND ti.status = 'pending'
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
                            // Update the task directly - keep status as pending
                            $stmt = $conn->prepare("
                                UPDATE tasks 
                                SET due_date = ?,
                                    due_time = ?,
                                    updated_at = CURRENT_TIMESTAMP 
                                WHERE id = ?
                            ");
                            $stmt->bind_param('ssi', $newDate, $newTime, $taskId);
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Failed to update task: " . $stmt->error);
                            }
                        } else {
                            // For recurring tasks
                            if ($task['instance_id']) {
                                // Update existing instance - keep status as pending
                                $stmt = $conn->prepare("
                                    UPDATE task_instances 
                                    SET due_time = ?,
                                        updated_at = CURRENT_TIMESTAMP 
                                    WHERE id = ?
                                ");
                                $stmt->bind_param('si', $newTime, $task['instance_id']);
                            } else {
                                // Create new instance for today with pending status
                                $stmt = $conn->prepare("
                                    INSERT INTO task_instances 
                                    (task_id, due_date, due_time, status, created_at, updated_at)
                                    VALUES (?, CURRENT_DATE, ?, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                                ");
                                $stmt->bind_param('is', $taskId, $newTime);
                            }
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Failed to update task instance: " . $stmt->error);
                            }
                        }
                        
                        $conn->commit();
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Task time updated successfully',
                            'new_due_date' => $newDate,
                            'new_due_time' => $newTime
                        ]);
                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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