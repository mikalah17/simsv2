<?php
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['role_type'] = 'user';

// Now include the dashboard PHP
require_once __DIR__ . '/user_pages/user_dashboard.php';
?>
