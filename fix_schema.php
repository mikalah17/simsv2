<?php
require_once __DIR__ . '/php/db_config.php';

try {
    $pdo = getPDO();
    
    echo "Modifying tables to add AUTO_INCREMENT...\n";
    
    // Disable foreign key checks temporarily
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    echo "Disabled foreign key constraints\n";
    
    // Modify tables
    $pdo->exec('ALTER TABLE asset MODIFY asset_id INT NOT NULL AUTO_INCREMENT');
    echo "✓ asset table modified\n";
    
    $pdo->exec('ALTER TABLE employee MODIFY employee_id INT NOT NULL AUTO_INCREMENT');
    echo "✓ employee table modified\n";
    
    $pdo->exec('ALTER TABLE dept MODIFY department_id INT NOT NULL AUTO_INCREMENT');
    echo "✓ dept table modified\n";
    
    $pdo->exec('ALTER TABLE request MODIFY request_id INT NOT NULL AUTO_INCREMENT');
    echo "✓ request table modified\n";
    
    // Re-enable foreign key checks
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "Re-enabled foreign key constraints\n";
    
    echo "\n✓ All tables have AUTO_INCREMENT enabled\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
