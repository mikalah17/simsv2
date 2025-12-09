<?php
session_start();

// Add a test user directly if needed
require_once __DIR__ . '/php/db_config.php';

try {
    $pdo = getPDO();
    
    // Check if test user exists
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
    $stmt->execute(['testuser@sims.local']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "Creating test user...\n";
        $hashedPass = password_hash('TestPass123', PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO users (first_name, last_name, email, password, role_type) VALUES (?, ?, ?, ?, ?)')
            ->execute(['Test', 'User', 'testuser@sims.local', $hashedPass, 'user']);
        $userId = $pdo->lastInsertId();
        echo "Test user created with ID: $userId\n";
    } else {
        $userId = $user['user_id'];
        echo "Test user exists with ID: $userId\n";
    }
    
    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['role_type'] = 'user';
    
    echo "\n✓ Session created. You can now access the dashboard.\n";
    echo "Redirecting to dashboard...\n";
    header('Location: http://localhost:8000/user_pages/user_dashboard.php');
    exit;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
