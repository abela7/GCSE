<?php
// Database connection test script

// Adjust the path if db_test.php is not in the root directory
require_once 'config/database.php'; 

echo "Attempting to connect to the database...<br>";

try {
    // Try getting the database connection
    $db = getDBConnection();
    
    // If we get here, the connection was successful
    echo "Database connection successful!<br>";
    
    // Optional: You can perform a simple query to further test
    // $stmt = $db->query("SELECT 1");
    // if ($stmt) {
    //     echo "Successfully executed a simple query.<br>";
    // } else {
    //     echo "Connected, but failed to execute a simple query.<br>";
    // }

    // Close connection explicitly if needed (PDO typically handles this)
    $db = null; 

} catch (Exception $e) {
    // Catch PDOException or the generic Exception from getDBConnection
    echo "Database Connection Failed!<br>";
    echo "Error: " . $e->getMessage() . "<br>";
}

?> 