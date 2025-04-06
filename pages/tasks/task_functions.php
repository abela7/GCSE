<?php
// GCSE/pages/tasks/task_functions.php
// Complete Code - FINAL Corrected Version (No ??, Fixed Syntax near end of updateTask)

// Define constants if not already defined globally (or include a config file)
if (!defined('DEFAULT_SNOOZE_MINUTES')) {
    define('DEFAULT_SNOOZE_MINUTES', 15); // Default snooze duration in minutes
}
if (!defined('SNOOZE_GRACE_PERIOD_MINUTES')) {
    define('SNOOZE_GRACE_PERIOD_MINUTES', 5); // Allow snoozing X minutes before due time
}

/**
 * Save a new task and its recurrence rules/initial instances if applicable.
 * Expects $data to be an array (like $_POST).
 * Uses isset() for defaults.
 */
function saveTask($conn, $data) {
    // Basic Validation
    if (empty($data['title'])) return ['success' => false, 'message' => 'Task title is required.'];
    if (empty($data['category_id'])) return ['success' => false, 'message' => 'Category is required.'];
    if (empty($data['task_type'])) return ['success' => false, 'message' => 'Task type is required.'];
    if (empty($data['priority'])) return ['success' => false, 'message' => 'Priority is required.'];
    if (empty($data['due_date'])) return ['success' => false, 'message' => 'Due date is required.'];
     if ($data['task_type'] === 'recurring' && empty($data['frequency'])) {
         return ['success' => false, 'message' => 'Frequency is required for recurring tasks.'];
     }
      if ($data['task_type'] === 'recurring' && $data['frequency'] === 'weekly' && empty($data['specific_days'])) {
         return ['success' => false, 'message' => 'Specific days (as JSON array string) are required for weekly recurring tasks.'];
     }


    try {
        if (!$conn->begin_transaction()) {
            throw new Exception("Failed to start transaction (saveTask)");
        }

        // Prepare statement for tasks table
        $stmt = $conn->prepare("INSERT INTO tasks (title, description, category_id, task_type, priority,
                               status, due_date, due_time, estimated_duration, is_active, created_at, updated_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

        if (!$stmt) {
            throw new Exception("Prepare failed (save task): " . $conn->error);
        }

        // Prepare variables using isset()
        $title = isset($data['title']) ? trim($data['title']) : '';
        $description = isset($data['description']) ? trim($data['description']) : null;
        $category_id = isset($data['category_id']) ? (int)$data['category_id'] : null;
        $task_type = isset($data['task_type']) ? $data['task_type'] : 'one-time';
        $priority = isset($data['priority']) ? $data['priority'] : 'medium';
        $status = isset($data['status']) ? $data['status'] : 'pending'; // Use status from form, default pending
        $due_date = isset($data['due_date']) ? $data['due_date'] : null;
        $dueTime = isset($data['due_time']) && !empty($data['due_time']) ? $data['due_time'] : null;
        $estDuration = isset($data['estimated_duration']) ? max(0, (int)$data['estimated_duration']) : 0;
        $isActive = isset($data['is_active']) && $data['is_active'] == '1' ? 1 : 0;

        // Bind parameters (ssissssisi - 10 params before ID)
        $stmt->bind_param("ssissssisi",
            $title,
            $description,
            $category_id,
            $task_type,
            $priority,
            $status,
            $due_date,
            $dueTime,
            $estDuration,
            $isActive
        );

        if (!$stmt->execute()) {
            throw new Exception("Error saving task: " . $stmt->error);
        }

        $taskId = $conn->insert_id;
        $stmt->close();

        // Handle recurring tasks
        if ($task_type === 'recurring') {
            handleRecurringTask($conn, $taskId, $data);
            generateInitialInstances($conn, $taskId, $data);
        }

        $conn->commit();
        return ['success' => true, 'message' => 'Task saved successfully', 'task_id' => $taskId];

    } catch (Exception $e) {
        if ($conn->ping() && $conn->inTransaction) {
             $conn->rollback();
        }
        error_log("saveTask Error: TaskData=" . print_r($data, true) . " Error=" . $e->getMessage());
        return ['success' => false, 'message' => 'Error saving task: ' . $e->getMessage()];
    }
}

/**
 * Update an existing task and its recurrence rules/instances if applicable.
 * Includes updating task_type and status from the modal form.
 * Uses isset() instead of ??.
 */
function updateTask($conn, $data) {
    // Basic Validation
    if (empty($data['task_id'])) return ['success' => false, 'message' => 'Task ID is missing.'];
    $taskId = (int)$data['task_id'];
    if ($taskId <= 0) return ['success' => false, 'message' => 'Invalid Task ID.'];

    // Check other required fields
    if (empty($data['title'])) return ['success' => false, 'message' => 'Task title is required.'];
    if (empty($data['category_id'])) return ['success' => false, 'message' => 'Category is required.'];
    if (empty($data['task_type'])) return ['success' => false, 'message' => 'Task type is required.'];
    if (empty($data['priority'])) return ['success' => false, 'message' => 'Priority is required.'];
    if (empty($data['status'])) return ['success' => false, 'message' => 'Status is required.'];
    if (empty($data['due_date'])) return ['success' => false, 'message' => 'Due date is required.'];
    if ($data['task_type'] === 'recurring' && empty($data['frequency'])) return ['success' => false, 'message' => 'Frequency is required for recurring tasks.'];
    if ($data['task_type'] === 'recurring' && $data['frequency'] === 'weekly' && empty($data['specific_days'])) return ['success' => false, 'message' => 'Specific days required for weekly recurring tasks.'];

     // Validate Status
    $allowedStatuses = ['pending', 'completed', 'snoozed', 'not_done', 'in_progress'];
    $statusValue = isset($data['status']) && in_array($data['status'], $allowedStatuses) ? $data['status'] : 'pending';
    // Validate Task Type
    $task_type = isset($data['task_type']) && in_array($data['task_type'], ['one-time', 'recurring']) ? $data['task_type'] : 'one-time';


    try {
        if (!$conn->begin_transaction()) {
             throw new Exception("Failed to start transaction (updateTask)");
        }

        // Fetch original task type
        $stmt_check = $conn->prepare("SELECT task_type FROM tasks WHERE id = ?");
        if(!$stmt_check) throw new Exception("Prepare failed (check task type): " . $conn->error);
        $stmt_check->bind_param("i", $taskId);
        $stmt_check->execute();
        $original_task_result = $stmt_check->get_result();
        if($original_task_result->num_rows === 0) throw new Exception("Task to update (ID: $taskId) not found.");
        $original_task = $original_task_result->fetch_assoc();
        $original_task_type = isset($original_task['task_type']) ? $original_task['task_type'] : 'one-time';
        $stmt_check->close();

        // Prepare the UPDATE statement
        $stmt = $conn->prepare("UPDATE tasks SET
            title = ?, description = ?, category_id = ?, task_type = ?, priority = ?, status = ?,
            due_date = ?, due_time = ?, estimated_duration = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");
         if (!$stmt) throw new Exception("Prepare failed (update task): " . $conn->error);

        // Prepare variables for binding using isset()
        $title = isset($data['title']) ? trim($data['title']) : '';
        $description = isset($data['description']) ? trim($data['description']) : null;
        $category_id = isset($data['category_id']) ? (int)$data['category_id'] : null;
        // $task_type validated above
        $priority = isset($data['priority']) ? $data['priority'] : 'medium';
        // $statusValue validated above
        $due_date = isset($data['due_date']) ? $data['due_date'] : null;
        $dueTime = isset($data['due_time']) && !empty($data['due_time']) ? $data['due_time'] : null;
        $estDuration = isset($data['estimated_duration']) ? max(0, (int)$data['estimated_duration']) : 0;
        $isActive = isset($data['is_active']) && $data['is_active'] == '1' ? 1 : 0;

        // Bind parameters (ssisssssiii - 11 params)
        $stmt->bind_param("ssisssssiii",
            $title, $description, $category_id, $task_type, $priority, $statusValue,
            $due_date, $dueTime, $estDuration, $isActive, $taskId
        );

        if (!$stmt->execute()) { throw new Exception("Error updating task details (ID: $taskId): " . $stmt->error); }
        $stmt->close();

        // --- Handle Recurrence Changes ---
        $recurrence_changed = false;
        // Logic to determine if recurrence actually changed (comparing old vs new)
        if ($original_task_type === 'recurring' && $task_type === 'recurring') {
            $stmt_old_rule = $conn->prepare("SELECT frequency, specific_days FROM task_recurrence_rules WHERE task_id = ?");
             if(!$stmt_old_rule) throw new Exception("Prepare failed (get old rule): ".$conn->error);
             $stmt_old_rule->bind_param("i", $taskId); $stmt_old_rule->execute();
             $old_rule = $stmt_old_rule->get_result()->fetch_assoc(); $stmt_old_rule->close();
             $new_specific_days_json = ($data['frequency'] === 'weekly' && isset($data['specific_days'])) ? $data['specific_days'] : null;
             if (!$old_rule || (isset($old_rule['frequency']) && $old_rule['frequency'] !== $data['frequency']) || ($old_rule['frequency'] === 'weekly' && (!isset($old_rule['specific_days']) || $old_rule['specific_days'] !== $new_specific_days_json)) || ($data['frequency'] === 'weekly' && !$new_specific_days_json)) { $recurrence_changed = true; }
        } elseif ($original_task_type !== $task_type) { $recurrence_changed = true; }

        if ($recurrence_changed) {
             error_log("Recurrence changed for Task ID: $taskId during update.");
             // Delete old rule and future instances
             $stmt_del_recur = $conn->prepare("DELETE FROM task_recurrence_rules WHERE task_id = ?");
             if(!$stmt_del_recur) throw new Exception("Prepare failed (del recur): ".$conn->error);
             $stmt_del_recur->bind_param("i", $taskId); $stmt_del_recur->execute(); $stmt_del_recur->close();
             $stmt_del_inst = $conn->prepare("DELETE FROM task_instances WHERE task_id = ? AND due_date >= ?");
             if(!$stmt_del_inst) throw new Exception("Prepare failed (del inst): ".$conn->error);
             $stmt_del_inst->bind_param("is", $taskId, $due_date); $stmt_del_inst->execute(); $stmt_del_inst->close();
             // Add new rule / generate instances IF new type is recurring
             if ($task_type === 'recurring') {
                handleRecurringTask($conn, $taskId, $data); // Handles inserting rule
                generateInitialInstances($conn, $taskId, $data); // Handles generating first few instances
             }
        } else { error_log("Recurrence NOT changed for Task ID: $taskId during update."); }
        // --- End Recurrence Handling ---

        $conn->commit(); // Commit successful update
        return ['success' => true, 'message' => 'Task updated successfully'];

    } catch (Exception $e) {
         if ($conn->ping() && $conn->inTransaction) {
             $conn->rollback(); // Rollback on any error
        }
        error_log("updateTask Error: TaskID=$taskId TaskData=" . print_r($data, true) . " Error=" . $e->getMessage());
        return ['success' => false, 'message' => 'Error updating task: ' . $e->getMessage()];
    }
} // End of updateTask function


/**
 * Handle inserting the recurrence rule for a task.
 */
function handleRecurringTask($conn, $taskId, $data) {
    // Validation
    if (empty($data['frequency'])) throw new Exception("Frequency missing in handleRecurringTask.");
    if ($data['frequency'] === 'weekly' && empty($data['specific_days'])) throw new Exception("Specific days JSON missing in handleRecurringTask for weekly task.");

    $stmt = $conn->prepare("INSERT INTO task_recurrence_rules (task_id, frequency, specific_days,
                           start_date, is_active, created_at, updated_at)
                           VALUES (?, ?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

     if (!$stmt) { throw new Exception("Prepare failed (handle recurring): " . $conn->error); }

    $specificDaysJson = null;
    if ($data['frequency'] === 'weekly') {
        if (isset($data['specific_days']) && json_decode($data['specific_days']) !== null) {
            $specificDaysJson = $data['specific_days'];
        } else { throw new Exception("Invalid or missing 'specific_days' JSON string for weekly task."); }
    }
    $startDate = isset($data['due_date']) ? $data['due_date'] : date('Y-m-d');

    $stmt->bind_param("isss", $taskId, $data['frequency'], $specificDaysJson, $startDate );

    if (!$stmt->execute()) { throw new Exception("Error saving recurrence rule: " . $stmt->error); }
    $stmt->close();
}


/**
 * Generate initial instances for a recurring task.
 */
function generateInitialInstances($conn, $taskId, $data) {
    $task_type = isset($data['task_type']) ? $data['task_type'] : 'one-time';
    if (empty($data['due_date']) || empty($data['frequency']) || $task_type !== 'recurring') {
        error_log("generateInitialInstances: Pre-condition fail TaskID=$taskId Data: ".print_r($data, true)); return;
    }
    try { $startDate = new DateTimeImmutable($data['due_date']); }
    catch (Exception $e) { error_log("generateInitialInstances: Invalid start date '{$data['due_date']}' TaskID=$taskId."); return; }
    $endDate = $startDate->modify('+4 weeks');
    $taskDueTime = isset($data['due_time']) && !empty($data['due_time']) ? $data['due_time'] : null;
    $stmt = $conn->prepare("INSERT IGNORE INTO task_instances (task_id, due_date, due_time, status, created_at, updated_at) VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
     if (!$stmt) { error_log("Prepare fail (gen init): ".$conn->error); return; }
    $currentDate = $startDate; $selectedDays = null;
    if ($data['frequency'] === 'weekly') {
        $decodedDays = isset($data['specific_days']) ? json_decode($data['specific_days'], true) : null;
        if(is_array($decodedDays)) { $selectedDays = array_map('intval',$decodedDays); }
        else { error_log("Invalid specific_days JSON '$specific_days_json' weekly TaskID=$taskId."); return; }
    }
    $iterations = 0; $maxIterations = 100;
    while ($currentDate <= $endDate && $iterations < $maxIterations) {
        $iterations++; $generate = false; $dayOfWeek = (int)$currentDate->format('w');
        if ($data['frequency'] === 'daily') { $generate = true; }
        elseif ($data['frequency'] === 'weekly' && is_array($selectedDays)) { if (in_array($dayOfWeek, $selectedDays, true)) { $generate = true; } }
        elseif ($data['frequency'] === 'monthly') { if ($currentDate->format('d') === $startDate->format('d') && $currentDate >= $startDate) { $generate = true; } }
        if ($generate) {
            $dateStr = $currentDate->format('Y-m-d');
            $bindTaskId = $taskId; $bindDateStr = $dateStr; $bindTimeStr = $taskDueTime;
            $stmt->bind_param("iss", $bindTaskId, $bindDateStr, $bindTimeStr);
            if (!$stmt->execute()) { error_log("Exec fail (gen init) Task=$taskId Date=$dateStr: ".$stmt->error); }
        }
        if ($data['frequency'] === 'monthly') { try { $currentDate = $currentDate->modify('+1 month'); } catch (Exception $e) { error_log("Error monthly date inc Task=$taskId: ".$e->getMessage()); break; } }
        else { $currentDate = $currentDate->modify('+1 day'); }
    }
    if($iterations >= $maxIterations) { error_log("Max iter reach gen TaskID=$taskId."); }
    $stmt->close();
}

/**
 * Delete a task and all related data.
 */
function deleteTask($conn, $taskId) {
     $taskId = (int)$taskId; if ($taskId <= 0) return ['success' => false, 'message' => 'Invalid Task ID.'];
    try { if (!$conn->begin_transaction()) throw new Exception("Failed to start transaction (deleteTask)");
        $stmt_del_inst = $conn->prepare("DELETE FROM task_instances WHERE task_id = ?");
        if(!$stmt_del_inst) throw new Exception("Prep fail (del inst): ".$conn->error); $stmt_del_inst->bind_param("i", $taskId); $stmt_del_inst->execute(); $stmt_del_inst->close();
        $stmt_del_recur = $conn->prepare("DELETE FROM task_recurrence_rules WHERE task_id = ?");
         if(!$stmt_del_recur) throw new Exception("Prep fail (del recur): ".$conn->error); $stmt_del_recur->bind_param("i", $taskId); $stmt_del_recur->execute(); $stmt_del_recur->close();
        $stmt_del_task = $conn->prepare("DELETE FROM tasks WHERE id = ?");
        if(!$stmt_del_task) throw new Exception("Prep fail (del task): ".$conn->error); $stmt_del_task->bind_param("i", $taskId); $stmt_del_task->execute();
        $affected_rows = $stmt_del_task->affected_rows; $stmt_del_task->close();
        if ($affected_rows === 0) { error_log("deleteTask Warn: TaskID=$taskId not found for deletion."); }
        $conn->commit(); return ['success' => true, 'message' => 'Task deleted successfully'];
    } catch (Exception $e) { if ($conn->ping() && $conn->inTransaction) { $conn->rollback(); } error_log("deleteTask Error: TaskID=$taskId Error=".$e->getMessage()); return ['success' => false, 'message' => 'Error deleting task: ' . $e->getMessage()]; }
}

/**
 * Toggle task active status.
 */
function toggleTaskStatus($conn, $taskId, $activate) { // Note: This function name is confusing, maybe rename to toggleTaskActiveStatus?
    $taskId = (int)$taskId; if ($taskId <= 0) return ['success' => false, 'message' => 'Invalid Task ID.']; $newStatus = $activate ? 1 : 0;
    try { if (!$conn->begin_transaction()) throw new Exception("Failed start transaction (toggle)");
        $stmt_task = $conn->prepare("UPDATE tasks SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
         if(!$stmt_task) throw new Exception("Prep fail (toggle task): ".$conn->error); $stmt_task->bind_param("ii", $newStatus, $taskId); $stmt_task->execute(); $affected_rows = $stmt_task->affected_rows; $stmt_task->close();
        if ($affected_rows === 0) { error_log("toggleTask Warn: TaskID=$taskId not found."); }
        $stmt_recur = $conn->prepare("UPDATE task_recurrence_rules SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE task_id = ?");
         if(!$stmt_recur) throw new Exception("Prep fail (toggle recur): ".$conn->error); $stmt_recur->bind_param("ii", $newStatus, $taskId); $stmt_recur->execute(); $stmt_recur->close();
        $conn->commit(); $actionText = $activate ? 'activated' : 'paused'; return ['success' => true, 'message' => "Task $actionText successfully"];
    } catch (Exception $e) { if ($conn->ping() && $conn->inTransaction) { $conn->rollback(); } error_log("toggleTaskStatus Error: TaskID=$taskId Error=".$e->getMessage()); return ['success' => false, 'message' => 'Error updating task active status: ' . $e->getMessage()]; }
}


/**
 * Fetch task details for editing.
 */
function getTask($conn, $taskId) {
     $taskId = (int)$taskId; if ($taskId <= 0) return ['success' => false, 'message' => 'Invalid Task ID.'];
    try {
        $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
         if(!$stmt) throw new Exception("Prep fail (get task): ".$conn->error); $stmt->bind_param("i", $taskId); $stmt->execute(); $result = $stmt->get_result(); $task = $result->fetch_assoc(); $stmt->close();
        if (!$task) { return ['success' => false, 'message' => 'Task not found.']; }
        if (isset($task['task_type']) && $task['task_type'] === 'recurring') {
            $stmt_recur = $conn->prepare("SELECT frequency, specific_days, start_date FROM task_recurrence_rules WHERE task_id = ? ORDER BY id DESC LIMIT 1");
            if(!$stmt_recur) throw new Exception("Prep fail (get recur rule): ".$conn->error); $stmt_recur->bind_param("i", $taskId); $stmt_recur->execute(); $recurrence = $stmt_recur->get_result()->fetch_assoc(); $stmt_recur->close();
            if ($recurrence) {
                $task['frequency'] = isset($recurrence['frequency']) ? $recurrence['frequency'] : null;
                $task['specific_days'] = isset($recurrence['specific_days']) ? $recurrence['specific_days'] : null;
                $task['start_date'] = isset($recurrence['start_date']) ? $recurrence['start_date'] : null;
            } else { $task['frequency'] = null; $task['specific_days'] = null; $task['start_date'] = null; error_log("Warn: Recur task ID=$taskId found but no rule."); }
        }
        return ['success' => true, 'task' => $task];
    } catch (Exception $e) { error_log("getTask Error: TaskID=$taskId Error=".$e->getMessage()); return ['success' => false, 'message' => 'Error fetching task details: ' . $e->getMessage()]; }
}


/**
 * Update the status (pending, completed, snoozed, not_done) of a task or instance.
 */
function updateTaskStatus($conn, $taskId, $newStatus, $instanceDate = null) {
    $taskId = (int)$taskId; if ($taskId <= 0) return ['success' => false, 'message' => 'Invalid Task ID.'];
    $validStatuses = ['pending', 'completed', 'snoozed', 'not_done']; if (!in_array($newStatus, $validStatuses)) { return ['success' => false, 'message' => "Invalid status ('$newStatus')."]; }
    try { if (!$conn->begin_transaction()) throw new Exception("Fail start trans (updateStatus)");
        $stmt_check = $conn->prepare("SELECT id, task_type FROM tasks WHERE id = ?");
        if (!$stmt_check) throw new Exception("Prep fail (chk type): ".$conn->error); $stmt_check->bind_param("i", $taskId); $stmt_check->execute(); $task_result = $stmt_check->get_result(); if ($task_result->num_rows === 0) throw new Exception("Task ($taskId) not found."); $task = $task_result->fetch_assoc(); $stmt_check->close();
        $affected_rows = 0;
        if ($task['task_type'] === 'one-time') {
            $stmt_update = $conn->prepare("UPDATE tasks SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status != ?");
            if (!$stmt_update) throw new Exception("Prep fail (upd one-time): ".$conn->error); $stmt_update->bind_param("sis", $newStatus, $taskId, $newStatus); if (!$stmt_update->execute()) throw new Exception("Exec fail (upd one-time): ".$stmt_update->error); $affected_rows = $stmt_update->affected_rows; $stmt_update->close();
        } else { // Recurring
            if (empty($instanceDate)) { if (in_array($newStatus, ['completed', 'not_done'])) { $instanceDate = date('Y-m-d'); error_log("Warn: instanceDate null for Task=$taskId. Default $instanceDate"); } else { throw new Exception("Instance date required for recurring task status updates."); } }
             if (!DateTime::createFromFormat('Y-m-d', $instanceDate)) { throw new Exception("Invalid date format: $instanceDate"); }
            $stmt_update = $conn->prepare("UPDATE task_instances SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE task_id = ? AND due_date = ? AND status != ?");
             if (!$stmt_update) throw new Exception("Prep fail (upd instance): ".$conn->error); $stmt_update->bind_param("siss", $newStatus, $taskId, $instanceDate, $newStatus); if (!$stmt_update->execute()) throw new Exception("Exec fail (upd instance): ".$stmt_update->error); $affected_rows = $stmt_update->affected_rows; $stmt_update->close();
            if ($affected_rows === 0) { error_log("Warn: No instance found/updated Task=$taskId Date=$instanceDate Status=$newStatus."); }
        }
        $conn->commit(); $message = ($affected_rows > 0) ? "Task status updated to $newStatus." : "Task status already $newStatus."; return ['success' => true, 'message' => $message];
    } catch (Exception $e) { if ($conn->ping() && $conn->inTransaction) { $conn->rollback(); } error_log("updateTaskStatus Err: Task=$taskId Stat=$newStatus Date=$instanceDate Err=".$e->getMessage()); return ['success' => false, 'message' => 'Error updating task status: ' . $e->getMessage()]; }
}

/**
 * Snooze a task or a specific task instance.
 */
function snoozeTask($conn, $taskId, $instanceDate = null) {
    $taskId = (int)$taskId; if ($taskId <= 0) return ['success' => false, 'message' => 'Invalid Task ID.'];
    try { if (!$conn->begin_transaction()) throw new Exception("Fail start trans (snooze)");
        $effectiveInstanceDate = isset($instanceDate) ? $instanceDate : date('Y-m-d');
        $fetch_sql = "SELECT t.id AS task_id, t.task_type, COALESCE(ti.due_date, t.due_date) AS current_due_date, COALESCE(ti.due_time, t.due_time) AS current_due_time, COALESCE(ti.status, t.status) AS current_status, ti.id AS instance_id FROM tasks t LEFT JOIN task_instances ti ON t.id = ti.task_id AND ti.due_date = ? WHERE t.id = ?";
        $stmt_fetch = $conn->prepare($fetch_sql); if (!$stmt_fetch) throw new Exception("Prep fail (fetch snooze): ".$conn->error); $stmt_fetch->bind_param("si", $effectiveInstanceDate, $taskId); $stmt_fetch->execute(); $result = $stmt_fetch->get_result(); if ($result->num_rows === 0) throw new Exception("Task ($taskId) not found."); $task = $result->fetch_assoc(); $stmt_fetch->close();
        if (in_array($task['current_status'], ['completed', 'not_done'])) { return ['success' => false, 'message' => 'Cannot snooze completed/not done task.']; }
        if (empty($task['current_due_date']) || empty($task['current_due_time'])) { return ['success' => false, 'message' => 'Cannot snooze task without due time.']; }
        $now = new DateTimeImmutable(); $currentDueDateTime = new DateTimeImmutable($task['current_due_date'] . ' ' . $task['current_due_time']); $snoozeAllowedAfter = $currentDueDateTime->modify('-' . SNOOZE_GRACE_PERIOD_MINUTES . ' minutes');
        if ($now < $snoozeAllowedAfter) { return ['success' => false, 'message' => 'Too early to snooze (Due ' . $currentDueDateTime->format('g:i A') . ').']; }
        $baseTimeForSnooze = ($now > $currentDueDateTime) ? $now : $currentDueDateTime; $newDueDateTime = $baseTimeForSnooze->modify('+' . DEFAULT_SNOOZE_MINUTES . ' minutes'); $newDate = $newDueDateTime->format('Y-m-d'); $newTime = $newDueDateTime->format('H:i:s');
        $affected_rows = 0;
        if ($task['task_type'] === 'one-time') {
            $stmt_update = $conn->prepare("UPDATE tasks SET due_date = ?, due_time = ?, status = 'snoozed', updated_at = CURRENT_TIMESTAMP WHERE id = ?"); if (!$stmt_update) throw new Exception("Prep fail (upd one-time snooze): ".$conn->error); $stmt_update->bind_param("ssi", $newDate, $newTime, $taskId); if (!$stmt_update->execute()) throw new Exception("Exec fail (upd one-time snooze): ".$stmt_update->error); $affected_rows = $stmt_update->affected_rows; $stmt_update->close();
        } else { // Recurring
            if ($task['instance_id']) {
                 $stmt_update = $conn->prepare("UPDATE task_instances SET due_date = ?, due_time = ?, status = 'snoozed', updated_at = CURRENT_TIMESTAMP WHERE id = ?"); if (!$stmt_update) throw new Exception("Prep fail (upd inst snooze): ".$conn->error); $stmt_update->bind_param("ssi", $newDate, $newTime, $task['instance_id']); if (!$stmt_update->execute()) throw new Exception("Exec fail (upd inst snooze): ".$stmt_update->error); $affected_rows = $stmt_update->affected_rows; $stmt_update->close();
             } else {
                  error_log("Snooze Warn: Instance for Task=$taskId Date=$effectiveInstanceDate not found. Creating snoozed instance."); $stmt_insert = $conn->prepare("INSERT INTO task_instances (task_id, due_date, due_time, status, created_at, updated_at) VALUES (?, ?, ?, 'snoozed', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"); if (!$stmt_insert) throw new Exception("Prep fail (ins snooze): ".$conn->error); $stmt_insert->bind_param("iss", $taskId, $newDate, $newTime); if (!$stmt_insert->execute()) throw new Exception("Exec fail (ins snooze): ".$stmt_insert->error); $affected_rows = $stmt_insert->affected_rows; $stmt_insert->close();
             }
        }
        if ($affected_rows > 0) { $conn->commit(); return ['success' => true, 'message' => 'Task snoozed until ' . $newDueDateTime->format('g:i A') . ($newDate != $effectiveInstanceDate ? ' on ' . $newDueDateTime->format('M d') : '')]; }
        else { throw new Exception("Snooze update affected 0 rows."); }
    } catch (Exception $e) { if ($conn->ping() && $conn->inTransaction) { $conn->rollback(); } error_log("snoozeTask Error: TaskID=$taskId InstanceDate=$instanceDate Error=".$e->getMessage()); return ['success' => false, 'message' => 'Error snoozing task: ' . $e->getMessage()]; }
}

/**
 * Generate task instances (Main periodic function).
 */
// function generateTaskInstances($conn) { ... Keep the existing corrected version from previous response ... }
// MAKE SURE the full generateTaskInstances function is included below


/**
 * Generate task instances (Main periodic function - make sure this is complete).
 * This function iterates through active recurring tasks and generates instances
 * for the near future if they don't already exist.
 */
function generateTaskInstances($conn) {
    // Prepare statement outside the loop
    $insert_stmt = $conn->prepare("INSERT IGNORE INTO task_instances (task_id, due_date, due_time, status, created_at, updated_at)
                                   VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    if (!$insert_stmt) {
        error_log("Prepare failed (generate instances - main): " . $conn->error);
        return; // Cannot proceed if statement fails
    }

    // Query for active, recurring tasks that need checking
    $query = "SELECT t.id, t.due_time, tr.frequency, tr.specific_days, tr.start_date,
              (SELECT MAX(due_date) FROM task_instances WHERE task_id = t.id) as last_instance_date
              FROM tasks t
              JOIN task_recurrence_rules tr ON t.id = tr.task_id
              WHERE t.task_type = 'recurring'
              AND t.is_active = 1
              AND tr.is_active = 1"; // Also check if the rule itself is active

    $result = $conn->query($query);
    if (!$result) {
        error_log("Error fetching recurring tasks for instance generation: " . $conn->error);
        $insert_stmt->close();
        return;
    }

    $today = new DateTimeImmutable();
    $generateUpTo = $today->modify('+4 weeks'); // How far ahead to generate

    while ($task = $result->fetch_assoc()) {
        $taskId = (int)$task['id'];
        $taskDueTime = isset($task['due_time']) ? $task['due_time'] : null;
        $frequency = isset($task['frequency']) ? $task['frequency'] : null;
        $specific_days_json = isset($task['specific_days']) ? $task['specific_days'] : null;
        $start_date_str = isset($task['start_date']) ? $task['start_date'] : null;
        $last_instance_date_str = isset($task['last_instance_date']) ? $task['last_instance_date'] : null;

        // Validate essential data for recurrence
        if (!$start_date_str || !$frequency) {
            error_log("Skipping task ID $taskId for instance generation: Missing start_date or frequency.");
            continue;
        }

        try {
            $taskStartDate = new DateTimeImmutable($start_date_str);
            // Determine the *actual* last date to check from. Use start_date if no instances exist yet.
            // Important: Modify to check from the day *before* start date if no instances exist yet,
            // so the loop correctly evaluates the actual start date itself.
            $lastKnownDate = $last_instance_date_str ? new DateTimeImmutable($last_instance_date_str) : $taskStartDate->modify('-1 day');
        } catch (Exception $e) {
            error_log("Invalid date for task {$taskId}: Start='{$start_date_str}' LastInstance='{$last_instance_date_str}'. Skipping generation.");
            continue;
        }

        // If already generated far enough, skip this task
        if ($lastKnownDate >= $generateUpTo) {
            continue;
        }

        // Calculate the first date we should *consider* generating for.
        $generationStartDate = $lastKnownDate->modify('+1 day');
        // Don't start generating before the task's official start date
        if ($generationStartDate < $taskStartDate) {
             $generationStartDate = $taskStartDate;
        }

        // --- Generation Loop ---
        $currentDate = $generationStartDate;
        $selectedDays = null;
        if ($frequency === 'weekly') {
             $decodedDays = $specific_days_json ? json_decode($specific_days_json, true) : null;
             if(is_array($decodedDays)) {
                 $selectedDays = array_map('intval', $decodedDays);
             } else {
                  error_log("Invalid specific_days JSON '$specific_days_json' for weekly task ID $taskId. Skipping generation loop.");
                  continue; // Skip this task if days JSON is bad
             }
        }

        $iterations = 0; $maxIterations = 100; // Safety break

        while ($currentDate <= $generateUpTo && $iterations < $maxIterations) {
            $iterations++;
            $generate = false;
            $dayOfWeek = (int)$currentDate->format('w'); // 0=Sun, 6=Sat

            if ($frequency === 'daily') {
                // Generate daily only on or after the start date
                if ($currentDate >= $taskStartDate) {
                    $generate = true;
                }
            } elseif ($frequency === 'weekly' && is_array($selectedDays)) {
                // Generate on specific days, only on or after the start date
                if (in_array($dayOfWeek, $selectedDays, true) && $currentDate >= $taskStartDate) {
                    $generate = true;
                }
            } elseif ($frequency === 'monthly') {
                 // Generate on the same day of the month as start date, on or after start date
                if ($currentDate->format('d') === $taskStartDate->format('d') && $currentDate >= $taskStartDate) {
                    $generate = true;
                }
            }

            if ($generate) {
                 $dateStr = $currentDate->format('Y-m-d');
                 $bindTaskId = $taskId; $bindDateStr = $dateStr; $bindTimeStr = $taskDueTime;
                 $insert_stmt->bind_param("iss", $bindTaskId, $bindDateStr, $bindTimeStr);
                 if (!$insert_stmt->execute()) {
                     // Log but continue (INSERT IGNORE handles duplicates)
                     error_log("Execute failed (generate instances) for task $taskId on $dateStr: " . $insert_stmt->error);
                 }
            }

            // Increment date correctly for next potential occurrence
             if ($frequency === 'monthly') {
                 // Handle monthly increment carefully
                 try {
                     // Strategy: add 1 month to the current date being checked
                     $currentDate = $currentDate->modify('+1 month');
                     // No longer need clamping logic if just checking day match 'd' == 'd'
                 } catch (Exception $e) {
                      error_log("Error calculating next monthly date for task $taskId from " . $currentDate->format('Y-m-d') . ": " . $e->getMessage());
                      break; // Exit loop for this task
                 }
             } else {
                 // Daily and Weekly just check the next calendar day
                 $currentDate = $currentDate->modify('+1 day');
             }
        } // end while loop for date range

         if($iterations >= $maxIterations) {
            error_log("Max iterations reached generating instances for task ID $taskId.");
         }
    } // End while loop through tasks
    $result->free();
    $insert_stmt->close();
} // End generateTaskInstances function

?>