<?php
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['role_type'] = 'user';
$_SESSION['first_name'] = 'Test';
$_SESSION['last_name'] = 'User';
$_SESSION['email'] = 'testuser@sims.local';
$_SESSION['logged_in'] = true;  // Add this flag
$_SESSION['role'] = 'user';     // legacy support

try {
    echo "Starting dashboard test...\n";
    
    require_once __DIR__ . '/php/db_config.php';
    echo "✓ DB config loaded\n";
    
    $pdo = getPDO();
    echo "✓ PDO connection successful\n";
    
    // Test asset query
    $result = $pdo->query('SELECT COUNT(*) as count FROM asset');
    $assetCount = $result->fetch(PDO::FETCH_ASSOC);
    echo "✓ Assets found: " . $assetCount['count'] . "\n";
    
    // Now load the dashboard
    echo "\nLoading dashboard...\n";
    require_once __DIR__ . '/user_pages/user_dashboard.php';
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    var_dump($e);
}
?>
