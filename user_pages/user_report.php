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
    
    // Get department statistics for line chart
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
    
    // Get top requested assets for pie chart
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
        LIMIT 5
    ')->fetchAll(PDO::FETCH_ASSOC);
    
    // Get weekly request data for line chart
    $weeklyData = $pdo->query('
        SELECT 
            DAYNAME(r.request_date) as day_name,
            DAYOFWEEK(r.request_date) as day_num,
            d.department_name,
            COUNT(r.request_id) as request_count
        FROM request r
        JOIN employee e ON r.employee_id = e.employee_id
        LEFT JOIN dept d ON e.department_id = d.department_id
        WHERE r.request_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY day_name, day_num, d.department_name
        ORDER BY day_num, d.department_name
    ')->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log('Report error: ' . $e->getMessage());
    $assetStats = [];
    $requestStats = [];
    $deptStats = [];
    $topAssets = [];
    $weeklyData = [];
}

// Prepare data for JavaScript
$deptNames = array_column($deptStats, 'department_name');
$deptCounts = array_column($deptStats, 'request_count');

$assetNames = array_column($topAssets, 'asset_name');
$assetQuantities = array_column($topAssets, 'total_quantity_requested');

// Organize weekly data by department
$weeklyByDept = [];
foreach ($weeklyData as $row) {
    $dept = $row['department_name'] ?? 'Unassigned';
    if (!isset($weeklyByDept[$dept])) {
        $weeklyByDept[$dept] = array_fill(0, 7, 0);
    }
    $dayIndex = $row['day_num'] - 1; // Sunday = 0, Saturday = 6
    $weeklyByDept[$dept][$dayIndex] = intval($row['request_count']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: 'DM Sans', Arial, sans-serif;
            background-image: url("../image/sims_bg.png");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            overflow: hidden;
        }

        /* Sidebar */
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

        .sidebar .logo {
            width: 250px;
            margin-bottom: 10px;
        }

        .nav-links {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: opacity 0.2s ease;
        }

        .sidebar.expanded .nav-links {
            opacity: 0;
            pointer-events: none;
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

        .sidebar a:hover {
            background: rgba(15, 27, 101, 0.67);
        }

        .sidebar a.active {
            background: rgba(15, 27, 101, 0.8);
        }

        .sidebar a .icon {
            width: 30px;
            height: 30px;
            margin-right: 12px;
        }

        .sidebar a.logout {
            margin-top: auto;
            margin-bottom: 40px;
            background: rgba(15, 27, 101, 0.67);
            justify-content: center;
        }

        .sidebar a.logout:hover {
            background: rgba(15, 27, 101, 0.85);
        }

        /* Profile Panel */
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

        .sidebar.expanded .profile-panel {
            opacity: 1;
            pointer-events: all;
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
            margin-top: auto;
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

        .profile-back:hover {
            background: rgba(15, 27, 101, 0.85);
        }

        .profile-back img {
            width: 20px;
            height: 20px;
        }

        /* Main content */
        .main-content {
            margin-left: 220px;
            padding: 40px;
            height: 100vh;
            box-sizing: border-box;
            transition: margin-left 0.3s ease;
        }

        .sidebar.expanded ~ .main-content {
            margin-left: 350px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .main-content h1 {
            color: white;
            margin: 0;
            font-size: 36px;
        }

        .generate-btn {
            padding: 12px 30px;
            border-radius: 25px;
            background: rgba(15, 27, 101, 0.8);
            border: none;
            color: white;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        .generate-btn:hover {
            background: rgba(15, 27, 101, 1);
        }

        /* Charts Container */
        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            height: calc(100vh - 140px);
        }

        .chart-panel {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(8px);
            padding: 25px;
            border-radius: 18px;
            display: flex;
            flex-direction: column;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .time-filter, .dept-filter {
            padding: 8px 20px;
            border-radius: 20px;
            background: rgba(100, 120, 200, 0.2);
            border: none;
            font-weight: 700;
            color: #0F1B65;
            font-size: 14px;
            cursor: pointer;
            appearance: none;
        }

        .chart-wrapper {
            flex: 1;
            position: relative;
            min-height: 0;
        }

        .legend {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #0F1B65;
            font-weight: 600;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/user_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header-section">
            <h1>Reports</h1>
            <button class="generate-btn" onclick="window.print()">Generate Report</button>
        </div>

        <div class="charts-container">
            <!-- Line Chart Panel -->
            <div class="chart-panel">
                <div class="chart-header">
                    <select class="time-filter" onchange="updateTimeRange(this.value)">
                        <option value="7">Last 7 days</option>
                        <option value="14">Last 14 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 3 months</option>
                        <option value="180">Last 6 months</option>
                        <option value="365">Last year</option>
                    </select>
                </div>
                <div class="chart-wrapper">
                    <canvas id="lineChart"></canvas>
                </div>
                <div class="legend" id="lineLegend">
                    <?php 
                    $colors = ['#4472C4', '#FFC000', '#FF0000', '#FF00FF', '#00B050', '#7030A0'];
                    $i = 0;
                    foreach ($deptStats as $dept): 
                        $color = $colors[$i % count($colors)];
                    ?>
                        <div class="legend-item">
                            <div class="legend-color" style="background: <?php echo $color; ?>;"></div>
                            <span><?php echo htmlspecialchars($dept['department_name'] ?? 'Unassigned'); ?></span>
                        </div>
                    <?php 
                        $i++;
                    endforeach; 
                    ?>
                </div>
            </div>

            <!-- Pie Chart Panel -->
            <div class="chart-panel">
                <div class="chart-header">
                    <select class="dept-filter" onchange="updatePieChart(this.value)">
                        <option value="all">All Departments - Top Assets</option>
                        <?php foreach ($deptStats as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department_name']); ?>">
                                <?php echo htmlspecialchars($dept['department_name'] ?? 'Unassigned'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="chart-wrapper">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
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

        // Prepare data from PHP
        const weeklyByDept = <?php echo json_encode($weeklyByDept); ?>;
        const assetNames = <?php echo json_encode($assetNames); ?>;
        const assetQuantities = <?php echo json_encode($assetQuantities); ?>;
        
        const colors = ['#4472C4', '#FFC000', '#FF0000', '#FF00FF', '#00B050', '#7030A0'];
        
        // Prepare datasets for line chart
        const datasets = [];
        let colorIndex = 0;
        for (const [dept, data] of Object.entries(weeklyByDept)) {
            datasets.push({
                label: dept,
                data: data,
                borderColor: colors[colorIndex % colors.length],
                backgroundColor: colors[colorIndex % colors.length],
                tension: 0.4
            });
            colorIndex++;
        }

        // Line Chart
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        const lineChart = new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                datasets: datasets.length > 0 ? datasets : [
                    {
                        label: 'No Data',
                        data: [0, 0, 0, 0, 0, 0, 0],
                        borderColor: '#CCCCCC',
                        backgroundColor: '#CCCCCC',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 10 }
                    }
                }
            }
        });

        // Pie Chart
        const pieColors = ['#FF6B6B', '#4ECDC4', '#FFE66D', '#95E1D3', '#A8E6CF', '#F38181', '#AA96DA'];
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        const pieChart = new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: assetNames.length > 0 ? assetNames : ['No Data'],
                datasets: [{
                    data: assetQuantities.length > 0 ? assetQuantities : [1],
                    backgroundColor: pieColors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'right',
                        labels: {
                            font: {
                                size: 14,
                                weight: 'bold',
                                family: 'DM Sans'
                            },
                            color: '#0F1B65',
                            padding: 15
                        }
                    }
                }
            }
        });

        function updateTimeRange(days) {
            // In a real implementation, this would fetch new data from the server
            alert('Time range updated to last ' + days + ' days. Refresh the page to see updated data.');
        }

        function updatePieChart(dept) {
            // In a real implementation, this would fetch department-specific asset data
            alert('Showing data for: ' + dept);
        }
    </script>

</body>
</html>