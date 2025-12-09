<?php
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/php/db_config.php';

echo "=== Users Table Structure ===\n\n";

try {
    $pdo = getPDO();
    
    // Get column info for users table
    $result = $pdo->query("DESCRIBE users");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns in 'users' table:\n";
    foreach ($columns as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")" . ($col['Null'] === 'NO' ? ' [NOT NULL]' : '') . "\n";
    }
    
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
