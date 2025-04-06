<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// Check if all required fields are present
$required_fields = ['title', 'category_id', 'due_date', 'task_type', 'priority', 'estimated_duration'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $_SESSION['message'] = "Missing required field: $field";
        $_SESSION['message_type'] = "danger";
        header("Location: index.php");
        exit;
    }
}

try {
    // Begin transaction
    $conn->begin_transaction();

    // Insert the task
    $stmt = $conn->prepare("
        INSERT INTO tasks (
            title, description, category_id, due_date, due_time, 
            task_type, priority, estimated_duration, status, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 1)
    ");

    $stmt->bind_param(
        "ssissssi",
        $_POST['title'],
        $_POST['description'],
        $_POST['category_id'],
        $_POST['due_date'],
        $_POST['due_time'],
        $_POST['task_type'],
        $_POST['priority'],
        $_POST['estimated_duration']
    );

    if (!$stmt->execute()) {
        throw new Exception("Error inserting task: " . $stmt->error);
    }

    $task_id = $conn->insert_id;

    // If it's a recurring task, create the recurrence rule
    if ($_POST['task_type'] === 'recurring') {
        $recurrence_stmt = $conn->prepare("
            INSERT INTO task_recurrence_rules (
                task_id, frequency, interval, start_date, end_date
            ) VALUES (?, 'daily', 1, ?, NULL)
        ");

        $recurrence_stmt->bind_param(
            "is",
            $task_id,
            $_POST['due_date']
        );

        if (!$recurrence_stmt->execute()) {
            throw new Exception("Error creating recurrence rule: " . $recurrence_stmt->error);
        }

        // Create the first instance
        $instance_stmt = $conn->prepare("
            INSERT INTO task_instances (
                task_id, due_date, due_time, status
            ) VALUES (?, ?, ?, 'pending')
        ");

        $instance_stmt->bind_param(
            "iss",
            $task_id,
            $_POST['due_date'],
            $_POST['due_time']
        );

        if (!$instance_stmt->execute()) {
            throw new Exception("Error creating task instance: " . $instance_stmt->error);
        }
    }

    // Commit transaction
    $conn->commit();

    $_SESSION['message'] = "Task created successfully!";
    $_SESSION['message_type'] = "success";
    header("Location: index.php");
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit;
}
?> 