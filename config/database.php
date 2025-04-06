<?php
function getDBConnection() {
    $host = 'localhost';
    $dbname = 'gcse_tracker';
    $username = 'root';
    $password = '';
    
    try {
        $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch(PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        throw new Exception("Database connection failed. Please try again later.");
    }
}
?> 