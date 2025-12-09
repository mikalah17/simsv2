<?php
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['role_type'] = 'user';
$_SESSION['first_name'] = 'Test';
$_SESSION['last_name'] = 'User';
$_SESSION['email'] = 'testuser@sims.local';
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'user';

// Determine which page to load
$page = $_GET['page'] ?? 'dashboard';
$valid_pages = ['dashboard', 'assets', 'requests', 'reports'];

if (!in_array($page, $valid_pages)) {
    $page = 'dashboard';
}

// Map page names to files
$page_files = [
    'dashboard' => 'user_pages/user_dashboard.php',
    'assets' => 'user_pages/user_asset.php',
    'requests' => 'user_pages/user_request.php',
    'reports' => 'user_pages/user_report.php'
];

require_once 'php/db_config.php';
require_once $page_files[$page];
?>
