<?php
require_once __DIR__ . '/php/db_config.php';

$pdo = getPDO();
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$pdo->exec('ALTER TABLE users MODIFY user_id INT NOT NULL AUTO_INCREMENT');
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
echo "✓ users table modified\n";
?>
