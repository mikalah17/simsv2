<?php
require_once __DIR__ . '/php/db_config.php';

try {
    $pdo = getPDO();
    
    // Check if we have test data already
    $checkAssets = $pdo->query('SELECT COUNT(*) as count FROM asset')->fetch(PDO::FETCH_ASSOC);
    
    if ($checkAssets['count'] == 0) {
        echo "Adding test assets...\n";
        
        $assets = [
            ['Computer Monitor', 15],
            ['Keyboard', 25],
            ['Mouse', 30],
            ['USB Cable', 50],
            ['Desk Lamp', 12],
            ['Office Chair', 8],
            ['Whiteboard', 5]
        ];
        
        foreach ($assets as [$name, $qty]) {
            $pdo->prepare('INSERT INTO asset (asset_name, asset_quantity) VALUES (?, ?)')
                ->execute([$name, $qty]);
        }
        echo "Assets added!\n";
    }
    
    // Check employees
    $checkEmp = $pdo->query('SELECT COUNT(*) as count FROM employee')->fetch(PDO::FETCH_ASSOC);
    
    if ($checkEmp['count'] == 0) {
        echo "Adding test employees...\n";
        
        // Add departments first
        $deptIds = [];
        $depts = ['IT Department', 'Finance', 'HR', 'Operations'];
        foreach ($depts as $dept) {
            $pdo->prepare('INSERT INTO dept (department_name) VALUES (?)')->execute([$dept]);
            $deptIds[] = $pdo->lastInsertId();
        }
        
        // Add employees
        $employees = [
            ['John', 'Doe', 'M', $deptIds[0]],
            ['Jane', 'Smith', 'A', $deptIds[1]],
            ['Bob', 'Johnson', 'R', $deptIds[2]],
            ['Alice', 'Williams', 'L', $deptIds[3]]
        ];
        
        foreach ($employees as [$fname, $lname, $mname, $dept_id]) {
            $pdo->prepare('INSERT INTO employee (employee_fname, employee_lname, employee_mname, department_id) VALUES (?, ?, ?, ?)')
                ->execute([$fname, $lname, $mname, $dept_id]);
        }
        echo "Employees and departments added!\n";
    }
    
    // Check requests
    $checkReq = $pdo->query('SELECT COUNT(*) as count FROM request')->fetch(PDO::FETCH_ASSOC);
    
    if ($checkReq['count'] == 0) {
        echo "Adding test requests...\n";
        
        // Get asset and employee IDs
        $assets = $pdo->query('SELECT asset_id FROM asset LIMIT 7')->fetchAll(PDO::FETCH_ASSOC);
        $employees = $pdo->query('SELECT employee_id FROM employee LIMIT 4')->fetchAll(PDO::FETCH_ASSOC);
        
        $requests = [
            [$assets[0]['asset_id'], 2, $employees[0]['employee_id'], '2024-01-15'],
            [$assets[1]['asset_id'], 5, $employees[1]['employee_id'], '2024-01-20'],
            [$assets[2]['asset_id'], 3, $employees[2]['employee_id'], '2024-01-25'],
            [$assets[3]['asset_id'], 10, $employees[3]['employee_id'], '2024-02-01'],
            [$assets[4]['asset_id'], 1, $employees[0]['employee_id'], '2024-02-05'],
            [$assets[5]['asset_id'], 2, $employees[1]['employee_id'], '2024-02-10'],
        ];
        
        foreach ($requests as [$asset_id, $qty, $emp_id, $date]) {
            $pdo->prepare('INSERT INTO request (asset_id, quantity_requested, employee_id, request_date) VALUES (?, ?, ?, ?)')
                ->execute([$asset_id, $qty, $emp_id, $date]);
        }
        echo "Requests added!\n";
    }
    
    echo "\n✓ Database has been populated with test data\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
