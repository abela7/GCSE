<?php
function getDBConnection() {
    $host = 'localhost';
    $dbname = 'abunetdg_web_app';
    $username = 'abunetdg_abel';
    $password = '2727@2121Abel';
    
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