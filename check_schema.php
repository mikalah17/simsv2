<?php
require_once __DIR__ . '/php/db_config.php';

try {
    $pdo = getPDO();
    
    // Check the current schema
    $tables = ['asset', 'employee', 'dept', 'request'];
    
    foreach ($tables as $table) {
        echo "\n=== $table ===\n";
        $result = $pdo->query("DESCRIBE $table");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Key'] . " | " . $row['Extra'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
