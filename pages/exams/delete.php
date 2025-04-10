<?php
// Include database connection
require_once '../../config/db_connect.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate exam ID
    if (isset($_POST['exam_id']) && is_numeric($_POST['exam_id'])) {
        $exam_id = intval($_POST['exam_id']);
        
        // Delete the exam from the database
        $delete_query = "DELETE FROM exams WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $exam_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Exam deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete exam: " . $conn->error;
        }
        
        $stmt->close();
    } else {
        $_SESSION['error'] = "Invalid exam ID.";
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

// Redirect back to exams page
header("Location: ../exam_countdown.php");
exit();
?> 