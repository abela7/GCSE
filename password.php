<?php
    $password = '0707@2121Abel'; // Your chosen password
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Password: " . htmlspecialchars($password) . "<br>";
    echo "Generated Hash: " . htmlspecialchars($hash);
    ?>