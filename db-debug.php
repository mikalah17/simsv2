<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

echo "=== Database Connection Debug ===\n\n";

require_once __DIR__ . '/php/db_config.php';

echo "DB Config:\n";
echo "  HOST: " . $DB_HOST . "\n";
echo "  NAME: " . $DB_NAME . "\n";
echo "  USER: " . $DB_USER . "\n";
echo "  PORT: " . $DB_PORT . "\n\n";

echo "Testing connection...\n";
try {
    $pdo = getPDO();
    echo "✓ SUCCESS - Connected to database\n\n";
    
    // Check if sims database exists and tables are there
    $result = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = 'sims'");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "Tables in 'sims' database: " . $row['cnt'] . "\n";
    
    // Try to query users table
    try {
        $result = $pdo->query("SELECT COUNT(*) as cnt FROM users");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        echo "Records in 'users' table: " . $row['cnt'] . "\n";
    } catch (Exception $e) {
        echo "ERROR querying users table: " . $e->getMessage() . "\n";
    }
    
} catch (Throwable $e) {
    echo "✗ FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
