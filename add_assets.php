<?php
require 'php/db_config.php';

try {
    $db = getPDO();
    
    $assets = [
        ['Laptop', 25],
        ['Desktop Computer', 15],
        ['Monitor', 30],
        ['Keyboard', 50],
        ['Mouse', 60],
        ['Printer', 5],
        ['Scanner', 3],
        ['Chair', 100],
        ['Desk', 80],
        ['Cabinet', 12],
    ];
    
    $sql = "INSERT INTO asset (asset_name, asset_quantity) VALUES (?, ?)";
    $stmt = $db->prepare($sql);
    
    foreach ($assets as $asset) {
        $stmt->execute([$asset[0], $asset[1]]);
        echo "Added: {$asset[0]} (Qty: {$asset[1]})\n";
    }
    
    echo "\nAssets added successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
