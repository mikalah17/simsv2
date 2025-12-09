<?php
require_once __DIR__ . '/php/db_config.php';
$pdo = getPDO();

echo "=== USERS TABLE SCHEMA ===\n";
$result = $pdo->query('DESCRIBE users');
foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}

echo "\n=== CHECKING DATA ===\n";
$stmt = $pdo->query('SELECT * FROM users LIMIT 1');
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    echo "Sample user: " . json_encode($user) . "\n";
} else {
    echo "No users found\n";
}
?>
