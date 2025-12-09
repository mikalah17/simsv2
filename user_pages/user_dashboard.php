<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('user');

// Get database connection
require_once __DIR__ . '/../php/db_config.php';

try {
    $pdo = getPDO();
    
    // Get only 4 most recent assets
    $assetsStmt = $pdo->query('SELECT asset_id, asset_name, asset_quantity FROM asset ORDER BY asset_name LIMIT 4');
    $assets = $assetsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get only 4 most recent requests with employee and asset info
    $requestsStmt = $pdo->query('
        SELECT 
            r.request_id,
            CONCAT(e.employee_fname, " ", e.employee_lname) as employee_name,
            a.asset_name,
            r.quantity_requested,
            d.department_name,
            r.request_date
        FROM request r
        JOIN employee e ON r.employee_id = e.employee_id
        JOIN asset a ON r.asset_id = a.asset_id
        LEFT JOIN dept d ON e.department_id = d.department_id
        ORDER BY r.request_date DESC
        LIMIT 4
    ');
    $requests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $assets = [];
    $requests = [];
    error_log('Dashboard error: ' . $e->getMessage());
}

include __DIR__ . '/user_dashboard.html';
?>
