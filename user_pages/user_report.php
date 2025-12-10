<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('user');

require_once __DIR__ . '/../php/db_config.php';

$pdo = getPDO();

// Get filter parameters
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;
$selectedDept = isset($_GET['dept']) ? $_GET['dept'] : 'all';

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
    
    // Get top requested assets for pie chart (filtered by department)
    if ($selectedDept === 'all') {
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
            HAVING total_quantity_requested > 0
            ORDER BY total_quantity_requested DESC
            LIMIT 5
        ')->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare('
            SELECT 
                a.asset_id,
                a.asset_name,
                COUNT(r.request_id) as request_count,
                SUM(r.quantity_requested) as total_quantity_requested,
                a.asset_quantity as current_quantity
            FROM asset a
            LEFT JOIN request r ON a.asset_id = r.asset_id
            LEFT JOIN employee e ON r.employee_id = e.employee_id
            LEFT JOIN dept d ON e.department_id = d.department_id
            WHERE d.department_name = ?
            GROUP BY a.asset_id, a.asset_name, a.asset_quantity
            HAVING total_quantity_requested > 0
            ORDER BY total_quantity_requested DESC
            LIMIT 5
        ');
        $stmt->execute([$selectedDept]);
        $topAssets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get ALL assets for the list (for PDF export only)
    $allAssets = $pdo->query('
        SELECT asset_id, asset_name, asset_quantity 
        FROM asset 
        ORDER BY asset_name
    ')->fetchAll(PDO::FETCH_ASSOC);
    
    // Get weekly request data for line chart (filtered by days)
    $stmt = $pdo->prepare('
        SELECT 
            DAYNAME(r.request_date) as day_name,
            DAYOFWEEK(r.request_date) as day_num,
            d.department_name,
            COUNT(r.request_id) as request_count
        FROM request r
        JOIN employee e ON r.employee_id = e.employee_id
        LEFT JOIN dept d ON e.department_id = d.department_id
        WHERE r.request_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY day_name, day_num, d.department_name
        ORDER BY day_num, d.department_name
    ');
    $stmt->execute([$days]);
    $weeklyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log('Report error: ' . $e->getMessage());
    $assetStats = [];
    $requestStats = [];
    $deptStats = [];
    $topAssets = [];
    $allAssets = [];
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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
            opacity: 1;
            transition: opacity 0.2s ease;
        }

        .sidebar.expanded .sidebar a.logout {
            opacity: 0;
            pointer-events: none;
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
            justify-content: flex-start;
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
            margin: 10px 0 40px 0;
        }

        .profile-buttons {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            align-items: center;
            padding-bottom: 30px;
        }

        .profile-logout {
            margin: 0;
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
            width: 90%;
            justify-content: center;
        }

        .profile-logout:hover {
            background: rgba(15, 27, 101, 0.85);
        }

        .profile-logout img {
            width: 20px;
            height: 20px;
        }

        .profile-back {
            margin: 0;
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
            width: 90%;
            justify-content: center;
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
            overflow-y: auto;
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

        .generate-btn:disabled {
            background: rgba(100, 100, 100, 0.8);
            cursor: wait;
        }

        /* Charts Container */
        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            height: calc(100vh - 140px);
            margin-bottom: 25px;
        }

        .chart-panel {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(8px);
            padding: 25px;
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h3 {
            margin: 0;
            color: #0F1B65;
            font-size: 20px;
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chart-wrapper canvas {
            max-height: 100%;
            width: 100% !important;
            height: 100% !important;
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

        /* Loading overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-content {
            background: white;
            padding: 30px 50px;
            border-radius: 15px;
            text-align: center;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0F1B65;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Print styles - hide UI elements */
        @media print {
            .sidebar, .generate-btn, .time-filter, .dept-filter {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            body {
                background: white;
            }
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/user_sidebar.php'; ?>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <h3 style="color: #0F1B65; margin: 0;">Generating PDF Report...</h3>
            <p style="color: #666; margin: 10px 0 0 0;">Please wait, this may take a moment</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header-section">
            <h1>Reports</h1>
            <button class="generate-btn" onclick="generatePDF()" id="generateBtn">Generate PDF Report</button>
        </div>

        <div id="reportContent">
            <div class="charts-container">
                <!-- Line Chart Panel -->
                <div class="chart-panel" id="lineChartPanel">
                    <div class="chart-header">
                        <h3>Request Trends by Department</h3>
                        <select class="time-filter" id="timeFilter" onchange="updateTimeRange(this.value)">
                            <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>Last 7 days</option>
                            <option value="14" <?php echo $days == 14 ? 'selected' : ''; ?>>Last 14 days</option>
                            <option value="30" <?php echo $days == 30 ? 'selected' : ''; ?>>Last 30 days</option>
                            <option value="90" <?php echo $days == 90 ? 'selected' : ''; ?>>Last 3 months</option>
                            <option value="180" <?php echo $days == 180 ? 'selected' : ''; ?>>Last 6 months</option>
                            <option value="365" <?php echo $days == 365 ? 'selected' : ''; ?>>Last year</option>
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
                <div class="chart-panel" id="pieChartPanel">
                    <div class="chart-header">
                        <h3>Top 5 Requested Assets</h3>
                        <select class="dept-filter" id="deptFilter" onchange="updatePieChart(this.value)">
                            <option value="all">All Departments</option>
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
                plugins: { 
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Number of Asset Requests Over Time by Department',
                        font: {
                            size: 16,
                            weight: 'bold',
                            family: 'DM Sans'
                        },
                        color: '#0F1B65',
                        padding: {
                            bottom: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' requests';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Day of the Week',
                            font: {
                                size: 13,
                                weight: 'bold',
                                family: 'DM Sans'
                            },
                            color: '#0F1B65'
                        },
                        grid: {
                            color: 'rgba(15, 27, 101, 0.1)'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 10 },
                        title: {
                            display: true,
                            text: 'Number of Requests',
                            font: {
                                size: 13,
                                weight: 'bold',
                                family: 'DM Sans'
                            },
                            color: '#0F1B65'
                        },
                        grid: {
                            color: 'rgba(15, 27, 101, 0.1)'
                        }
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
                    },
                    title: {
                        display: true,
                        text: 'Total Quantity Requested by Asset Type',
                        font: {
                            size: 16,
                            weight: 'bold',
                            family: 'DM Sans'
                        },
                        color: '#0F1B65',
                        padding: {
                            bottom: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value + ' units (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        function updateTimeRange(days) {
            window.location.href = `user_report.php?days=${days}`;
        }

        function updatePieChart(dept) {
            window.location.href = `user_report.php?dept=${encodeURIComponent(dept)}`;
        }

        // PDF Generation Function
        async function generatePDF() {
            const btn = document.getElementById('generateBtn');
            const overlay = document.getElementById('loadingOverlay');
            
            // Disable button and show loading
            btn.disabled = true;
            overlay.classList.add('active');
            
            try {
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('p', 'mm', 'a4');
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                let yOffset = 20;

                // Title
                pdf.setFontSize(24);
                pdf.setTextColor(15, 27, 101);
                pdf.text('Inventory Management Report', pageWidth / 2, yOffset, { align: 'center' });
                
                yOffset += 10;
                pdf.setFontSize(12);
                pdf.setTextColor(100, 100, 100);
                pdf.text('Generated on: ' + new Date().toLocaleString(), pageWidth / 2, yOffset, { align: 'center' });
                
                yOffset += 15;

                // Capture Line Chart
                const lineChartCanvas = document.getElementById('lineChart');
                const lineChartImg = lineChartCanvas.toDataURL('image/png');
                pdf.setFontSize(14);
                pdf.setTextColor(15, 27, 101);
                pdf.text('Request Trends by Department (Last 7 Days)', 15, yOffset);
                yOffset += 8;
                pdf.addImage(lineChartImg, 'PNG', 15, yOffset, 180, 90);
                yOffset += 95;

                // Check if we need a new page
                if (yOffset > pageHeight - 100) {
                    pdf.addPage();
                    yOffset = 20;
                }

                // Capture Pie Chart
                const pieChartCanvas = document.getElementById('pieChart');
                const pieChartImg = pieChartCanvas.toDataURL('image/png');
                pdf.setFontSize(14);
                pdf.setTextColor(15, 27, 101);
                pdf.text('Top 5 Requested Assets', 15, yOffset);
                yOffset += 8;
                pdf.addImage(pieChartImg, 'PNG', 35, yOffset, 140, 90);
                yOffset += 95;

                // New page for assets list
                pdf.addPage();
                yOffset = 20;

                // Assets List Title
                pdf.setFontSize(16);
                pdf.setTextColor(15, 27, 101);
                pdf.text('Current Asset Inventory', 15, yOffset);
                yOffset += 10;

                // Table header
                pdf.setFillColor(15, 27, 101);
                pdf.rect(15, yOffset, 180, 10, 'F');
                pdf.setTextColor(255, 255, 255);
                pdf.setFontSize(12);
                pdf.text('Asset Name', 20, yOffset + 7);
                pdf.text('Quantity', 160, yOffset + 7);
                yOffset += 15;

                // Table rows
                pdf.setTextColor(50, 50, 50);
                pdf.setFontSize(11);
                
                <?php foreach ($allAssets as $index => $asset): ?>
                    if (yOffset > pageHeight - 20) {
                        pdf.addPage();
                        yOffset = 20;
                        
                        // Repeat header
                        pdf.setFillColor(15, 27, 101);
                        pdf.rect(15, yOffset, 180, 10, 'F');
                        pdf.setTextColor(255, 255, 255);
                        pdf.setFontSize(12);
                        pdf.text('Asset Name', 20, yOffset + 7);
                        pdf.text('Quantity', 160, yOffset + 7);
                        yOffset += 15;
                        pdf.setTextColor(50, 50, 50);
                        pdf.setFontSize(11);
                    }
                    
                    // Alternating row colors
                    <?php if ($index % 2 == 0): ?>
                        pdf.setFillColor(245, 245, 245);
                    <?php else: ?>
                        pdf.setFillColor(255, 255, 255);
                    <?php endif; ?>
                    pdf.rect(15, yOffset - 5, 180, 8, 'F');
                    
                    pdf.text('<?php echo addslashes($asset['asset_name']); ?>', 20, yOffset);
                    pdf.text('<?php echo $asset['asset_quantity']; ?> units', 160, yOffset);
                    yOffset += 8;
                <?php endforeach; ?>

                // Save PDF
                const fileName = 'SIMS_Report_' + new Date().toISOString().slice(0, 10) + '.pdf';
                pdf.save(fileName);
                
            } catch (error) {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again.');
            } finally {
                // Re-enable button and hide loading
                btn.disabled = false;
                overlay.classList.remove('active');
            }
        }
    </script>

</body>
</html>