<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/php/db_config.php';

echo "=== Testing User Registration ===\n\n";

try {
    $pdo = getPDO();
    
    // Test INSERT with sample data
    $hash = password_hash('TestPassword123', PASSWORD_DEFAULT);
    $nextId = 1;
    
    echo "Attempting INSERT with:\n";
    echo "  ID: $nextId\n";
    echo "  First Name: TestUser\n";
    echo "  Last Name: Test\n";
    echo "  Email: test@example.com\n";
    echo "  Password Hash: " . substr($hash, 0, 20) . "...\n\n";
    
    $insert = $pdo->prepare('INSERT INTO users (user_id, first_name, middle_name, last_name, email, password_hash, role_type) VALUES (:id, :fn, :mn, :ln, :email, :hash, :role)');
    $insert->execute([
        ':id' => $nextId,
        ':fn' => 'TestUser',
        ':mn' => null,
        ':ln' => 'Test',
        ':email' => 'test@example.com',
        ':hash' => $hash,
        ':role' => 'user'
    ]);
    
    echo "✓ INSERT SUCCESSFUL!\n";
    echo "User registered successfully.\n";
    
} catch (Throwable $e) {
    echo "✗ INSERT FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
?>
