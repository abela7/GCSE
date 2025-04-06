<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'abunetdg_abel';
$db_pass = '2727@2121Abel';
$db_name = 'abunetdg_web_app';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 