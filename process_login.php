<?php
session_start();

// Include database connection
require_once 'includes/db_connect.php';

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if username and password are set and not empty
    if (empty(trim($_POST["username"])) || empty(trim($_POST["password"]))) {
        header("location: login.php?error=missing_fields");
        exit;
    }
    
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    
    // Prepare SQL query to fetch user by username
    $sql = "SELECT id, username, password_hash FROM users WHERE username = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("s", $param_username);
        
        // Set parameters
        $param_username = $username;
        
        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Store result
            $stmt->store_result();
            
            // Check if username exists
            if ($stmt->num_rows == 1) {
                // Bind result variables
                $stmt->bind_result($id, $username, $hashed_password);
                if ($stmt->fetch()) {
                    // Verify password
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
                        session_regenerate_id(true); // Prevent session fixation
                        
                        // Store data in session variables
                        $_SESSION["user_logged_in"] = true;
                        $_SESSION["user_id"] = $id;
                        $_SESSION["username"] = $username;
                        
                        // Optional: Update last login time
                        $update_sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
                        if ($update_stmt = $conn->prepare($update_sql)) {
                            $update_stmt->bind_param("i", $id);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                        
                        // Redirect user to welcome page (e.g., index.php or dashboard)
                        header("location: index.php"); // Adjust redirect location if needed
                        exit;
                    } else {
                        // Password is not valid
                        header("location: login.php?error=invalid_credentials");
                        exit;
                    }
                }
            } else {
                // Username doesn't exist
                header("location: login.php?error=invalid_credentials");
                exit;
            }
        } else {
            // SQL execution error
            // In production, log this error instead of showing it
            error_log("Oops! Something went wrong executing statement: " . $stmt->error);
            header("location: login.php?error=server_error");
            exit;
        }
        
        // Close statement
        $stmt->close();
    } else {
        // SQL preparation error
        error_log("Oops! Something went wrong preparing statement: " . $conn->error);
        header("location: login.php?error=server_error");
        exit;
    }
    
    // Close connection
    $conn->close();
} else {
    // If not POST method, redirect to login page
    header("location: login.php");
    exit;
}
?> 