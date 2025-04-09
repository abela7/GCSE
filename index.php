<?php
require_once __DIR__ . '/includes/auth_check.php';

// Redirect to dashboard
header('Location: pages/dashboard.php');
exit;
?>