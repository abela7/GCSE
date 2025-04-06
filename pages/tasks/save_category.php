<?php
session_start();
require_once '../../includes/db_connect.php';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);
    $color = trim($_POST['color']);
    $id = isset($_POST['id']) && !empty($_POST['id']) ? intval($_POST['id']) : null;
    
    // Validate required fields
    if (empty($name) || empty($icon) || empty($color)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: categories.php");
        exit();
    }
    
    try {
        if ($id) {
            // Update existing category
            $stmt = $conn->prepare("UPDATE task_categories SET name = ?, icon = ?, color = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $icon, $color, $id);
        } else {
            // Create new category
            $stmt = $conn->prepare("INSERT INTO task_categories (name, icon, color) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $icon, $color);
        }
        
        if ($stmt->execute()) {
            // Success - redirect back to categories page
            $_SESSION['success'] = $id ? "Category updated successfully!" : "Category created successfully!";
            header("Location: categories.php?success=" . ($id ? "updated" : "created"));
            exit();
        } else {
            $_SESSION['error'] = "Database error: " . $stmt->error;
            header("Location: categories.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: categories.php");
        exit();
    }
} else {
    // Not a POST request
    header("Location: categories.php");
    exit();
}
?>