<?php
session_start();
require_once '../../includes/db_connect.php';

// Check if form is submitted and has the necessary data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        $id = (int)$_POST['id'];
        
        // First, check if there are any tasks using this category
        $check_stmt = $conn->prepare("SELECT COUNT(*) as task_count FROM tasks WHERE category_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['task_count'] > 0) {
            throw new Exception("Cannot delete category: There are {$row['task_count']} tasks using this category. Please reassign these tasks first.");
        }
        
        // Delete the category
        $stmt = $conn->prepare("DELETE FROM task_categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // Commit the transaction
            $conn->commit();
            
            $_SESSION['message'] = "Category deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            throw new Exception("Error deleting category: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "danger";
}

// Redirect back to categories page
header("Location: categories.php");
exit;
?>