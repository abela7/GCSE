<?php
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gcse_tracker";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");

// Function to safely escape user input
function clean_input($conn, $data) {
    return $conn->real_escape_string(trim($data));
}

// Function to handle database errors
function db_error($conn) {
    return "Database error: " . $conn->error;
}

// Function to close the database connection
function close_connection($conn) {
    $conn->close();
}
?>