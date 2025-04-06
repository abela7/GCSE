<?php
ob_start();
require_once '../config/db_connect.php';
include '../includes/header.php';

if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "No assignment ID provided.";
    header('Location: assignments.php');
    exit;
}

$assignment_id = mysqli_real_escape_string($conn, $_GET['id']);

// First, verify the assignment exists
$sql = "SELECT title FROM access_assignments WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result->fetch_assoc()) {
    $_SESSION['error_message'] = "Assignment not found.";
    header('Location: assignments.php');
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Delete related records first
    // 1. Delete progress records
    $sql = "DELETE FROM assignment_criteria_progress WHERE assignment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();

    // 2. Delete progress log entries
    $sql = "DELETE FROM assignment_progress_log WHERE assignment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();

    // 3. Delete guidance items
    $sql = "DELETE FROM assignment_guidance WHERE assignment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();

    // 4. Delete assessment criteria
    $sql = "DELETE FROM assessment_criteria WHERE assignment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();

    // Finally, delete the assignment itself
    $sql = "DELETE FROM access_assignments WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    $_SESSION['success_message'] = "Assignment and all related data deleted successfully.";
    header('Location: assignments.php');
    exit;

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $_SESSION['error_message'] = "Error deleting assignment: " . $e->getMessage();
    header('Location: assignments.php');
    exit;
}
?> 