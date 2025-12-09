<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('user');

require_once __DIR__ . '/../php/db_config.php';

$pdo = getPDO();

try {
    // Get inventory statistics
    $assetStats = $pdo->query('
        SELECT 
            COUNT(*) as total_assets,
            SUM(asset_quantity) as total_quantity,
            AVG(asset_quantity) as avg_quantity
        FROM asset
    ')->fetch(PDO::FETCH_ASSOC);
    
    // Get request statistics
    $requestStats = $pdo->query('
        SELECT 
            COUNT(*) as total_requests,
            SUM(quantity_requested) as total_quantity_requested
        FROM request
    ')->fetch(PDO::FETCH_ASSOC);
    
    // Get department statistics
    $deptStats = $pdo->query('
        SELECT 
            d.department_name,
            COUNT(r.request_id) as request_count,
            SUM(r.quantity_requested) as quantity_requested
        FROM dept d
        LEFT JOIN employee e ON d.department_id = e.department_id
        LEFT JOIN request r ON e.employee_id = r.employee_id
        GROUP BY d.department_id, d.department_name
        ORDER BY request_count DESC
    ')->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top requested assets
    $topAssets = $pdo->query('
        SELECT 
            a.asset_id,
            a.asset_name,
            COUNT(r.request_id) as request_count,
            SUM(r.quantity_requested) as total_quantity_requested,
            a.asset_quantity as current_quantity
        FROM asset a
        LEFT JOIN request r ON a.asset_id = r.asset_id
        GROUP BY a.asset_id, a.asset_name, a.asset_quantity
        ORDER BY total_quantity_requested DESC
        LIMIT 10
    ')->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent activity
    $recentActivity = $pdo->query('
        SELECT 
            r.request_id,
            a.asset_name,
            r.quantity_requested,
            CONCAT(e.employee_fname, " ", e.employee_lname) as employee_name,
            d.department_name,
            r.request_date
        FROM request r
        JOIN asset a ON r.asset_id = a.asset_id
        JOIN employee e ON r.employee_id = e.employee_id
        LEFT JOIN dept d ON e.department_id = d.department_id
        ORDER BY r.request_date DESC
        LIMIT 20
    ')->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log('Report error: ' . $e->getMessage());
    $assetStats = [];
    $requestStats = [];
    $deptStats = [];
    $topAssets = [];
    $recentActivity = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background-color: #1a1a2e;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100vh;
            font-family: 'DM Sans', Arial, sans-serif;
            background-image: url("../image/sims_bg.png");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 220px;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            box-shadow: 2px 0 15px rgba(0,0,0,0.2);
            transition: width 0.3s ease;
            z-index: 100;
            overflow-y: auto;
            scrollbar-width: none;
        }

        .sidebar::-webkit-scrollbar {
            display: none;
        }

        .sidebar.expanded {
            width: 350px;
        }

        .sidebar.expanded .logout {
            opacity: 0;
            pointer-events: none;
        }

        .sidebar.expanded .nav-links {
            opacity: 0;
            pointer-events: none;
        }

        .sidebar.expanded .logo {
            opacity: 0;
            pointer-events: none;
        }

        .sidebar.expanded .profile-panel {
            opacity: 1;
            pointer-events: all;
        }

        .main-content {
            margin-left: 220px;
            padding: 40px;
            min-height: 100vh;
            box-sizing: border-box;
            padding-bottom: 80px;
            overflow-y: auto;
            transition: margin-left 0.3s ease;
        }

        .sidebar.expanded ~ .main-content {
            margin-left: 350px;
        }

        .main-content h1 {
            color: white;
            margin-top: 0;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            color: white;
            text-align: center;
        }

        .stat-card h3 {
            margin: 0 0 15px 0;
            font-size: 14px;
            font-weight: 500;
            color: rgba(255,255,255,0.8);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: white;
            margin: 0;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            color: white;
            margin-bottom: 30px;
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 24px;
            color: white;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table thead {
            background: rgba(15, 27, 101, 0.2);
        }

        .table th {
            padding: 12px;
            text-align: left;
            color: white;
            font-weight: 600;
            border-bottom: 2px solid rgba(255,255,255,0.2);
        }

        .table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: white;
        }

        .table tbody tr:hover {
            background: rgba(255,255,255,0.05);
        }

        .progress-bar {
            background: rgba(255,255,255,0.1);
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00d4ff, #0f1b65);
            transition: width 0.3s ease;
        }

        .nav-links {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar a {
            width: 90%;
            padding: 12px 20px;
            margin: 8px 0;
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: 700;
            border-radius: 10px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            box-sizing: border-box;
        }

        .sidebar a .icon {
            width: 30px;
            height: 30px;
            margin-right: 12px;
        }

        .sidebar a:hover {
            background: rgba(15, 27, 101, 0.67);
        }

        .sidebar a.active {
            background: rgba(15, 27, 101, 0.8);
        }

        .sidebar a.logout {
            margin-top: auto;
            margin-bottom: 40px;
            background: rgba(15, 27, 101, 0.67);
            width: 90%;
            justify-content: center;
        }

        .sidebar a.logout:hover {
            background: rgba(15, 27, 101, 0.85);
        }

        .sidebar .logo {
            width: 250px;
            margin-bottom: 10px;
        }

        .profile-panel {
            position: absolute;
            top: 120px;
            left: 0;
            width: 100%;
            height: calc(100% - 120px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 30px;
            box-sizing: border-box;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease 0.1s;
        }

        .profile-name {
            color: white;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin: 5px 0;
            line-height: 1.2;
        }

        .profile-email {
            color: white;
            font-size: 16px;
            text-align: center;
            margin: 10px 0 0 0;
        }

        .profile-logout {
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 12px 30px;
            background: rgba(15, 27, 101, 0.67);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .profile-logout:hover {
            background: rgba(15, 27, 101, 0.85);
        }

        .profile-logout img {
            width: 20px;
            height: 20px;
        }

        .profile-back {
            margin-top: auto;
            margin-bottom: 0;
            padding: 12px 30px;
            background: rgba(15, 27, 101, 0.67);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .profile-back:hover {
            background: rgba(15, 27, 101, 0.85);
        }

        .profile-back img {
            width: 20px;
            height: 20px;
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/user_sidebar.php'; ?>

    <div class="main-content">
        <h1>Reports & Analytics</h1>

        <!-- Key Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Assets</h3>
                <p class="stat-value"><?php echo intval($assetStats['total_assets'] ?? 0); ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Quantity</h3>
                <p class="stat-value"><?php echo intval($assetStats['total_quantity'] ?? 0); ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Requests</h3>
                <p class="stat-value"><?php echo intval($requestStats['total_requests'] ?? 0); ?></p>
            </div>
            <div class="stat-card">
                <h3>Quantity Requested</h3>
                <p class="stat-value"><?php echo intval($requestStats['total_quantity_requested'] ?? 0); ?></p>
            </div>
        </div>

        <!-- Top Requested Assets -->
        <div class="card">
            <h2>Top Requested Assets</h2>
            <?php if (!empty($topAssets)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Asset Name</th>
                            <th>Request Count</th>
                            <th>Total Quantity Requested</th>
                            <th>Current Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topAssets as $asset): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                <td><?php echo intval($asset['request_count']); ?></td>
                                <td><?php echo intval($asset['total_quantity_requested'] ?? 0); ?></td>
                                <td><?php echo intval($asset['current_quantity']); ?> units</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #999; text-align: center; padding: 20px;">No data available</p>
            <?php endif; ?>
        </div>

        <!-- Department Statistics -->
        <div class="card">
            <h2>Department Activity</h2>
            <?php if (!empty($deptStats)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Request Count</th>
                            <th>Total Quantity Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deptStats as $dept): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($dept['department_name'] ?? 'Unassigned'); ?></td>
                                <td><?php echo intval($dept['request_count']); ?></td>
                                <td><?php echo intval($dept['quantity_requested'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #999; text-align: center; padding: 20px;">No data available</p>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <h2>Recent Requests (Last 20)</h2>
            <?php if (!empty($recentActivity)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Asset</th>
                            <th>Quantity</th>
                            <th>Employee</th>
                            <th>Department</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivity as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['request_date']); ?></td>
                                <td><?php echo htmlspecialchars($activity['asset_name']); ?></td>
                                <td><?php echo intval($activity['quantity_requested']); ?></td>
                                <td><?php echo htmlspecialchars($activity['employee_name']); ?></td>
                                <td><?php echo htmlspecialchars($activity['department_name'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #999; text-align: center; padding: 20px;">No recent activity</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        let profileOpen = false;

        function toggleProfile(event) {
            event.preventDefault();
            profileOpen = !profileOpen;
            
            if (profileOpen) {
                sidebar.classList.add('expanded');
            } else {
                sidebar.classList.remove('expanded');
            }
        }

        document.addEventListener('click', function(event) {
            if (profileOpen && !sidebar.contains(event.target)) {
                sidebar.classList.remove('expanded');
                profileOpen = false;
            }
        });
    </script>

</body>
</html>
