<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('user');

// Get database connection
require_once __DIR__ . '/../php/db_config.php';

try {
    $pdo = getPDO();
    
    // Get ALL assets (removed LIMIT)
    $assetsStmt = $pdo->query('SELECT asset_id, asset_name, asset_quantity FROM asset ORDER BY asset_name');
    $assets = $assetsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get ALL requests with employee and asset info (removed LIMIT)
    $requestsStmt = $pdo->query('
        SELECT 
            r.request_id,
            CONCAT(e.employee_fname, " ", e.employee_lname) as employee_name,
            a.asset_name,
            r.quantity_requested,
            d.department_name,
            r.request_date
        FROM request r
        JOIN employee e ON r.employee_id = e.employee_id
        JOIN asset a ON r.asset_id = a.asset_id
        LEFT JOIN dept d ON e.department_id = d.department_id
        ORDER BY r.request_date DESC
    ');
    $requests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $assets = [];
    $requests = [];
    error_log('Dashboard error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
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
            background:  rgba(15, 27, 101, 0.67);
        }

        .sidebar a:active {
            background: rgba(0,0,0,0.5);
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
            width: 90%;
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
            padding-bottom: 80px;
            overflow: hidden;
            transition: margin-left 0.3s ease;
        }

        .sidebar.expanded ~ .main-content {
            margin-left: 350px;
        }

        .main-content h1 {
            color: white;
            margin-bottom: 30px;
        }

        .cards {
            display: flex;
            gap: 20px; 
            margin-right: 20px;
            height: calc(100vh - 150px);
        }

        .card {
            flex: 1;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            color: white;
            display: flex;
            flex-direction: column;
            text-align: left;
            max-height: 100%;
        }

        .card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 35px;
            flex-shrink: 0;
        }

        .inner-cards {
            display: flex;
            flex-direction: column;
            gap: 10px;
            overflow-y: auto;
            padding-right: 10px;
        }

        /* Custom Scrollbar */
        .inner-cards::-webkit-scrollbar {
            width: 8px;
        }

        .inner-cards::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .inner-cards::-webkit-scrollbar-thumb {
            background: rgba(15, 27, 101, 0.67);
            border-radius: 10px;
        }

        .inner-cards::-webkit-scrollbar-thumb:hover {
            background: rgba(15, 27, 101, 0.85);
        }

        .inner-card {
            background: rgba(255, 255, 255, 0.8);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            color: #0F1B65;
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-size: 14px;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include __DIR__ . '/user_sidebar.php'; ?>

    <!-- Main content -->

    <!-- Main content -->
    <div class="main-content">
        <h1>Dashboard</h1>

        <div class="cards">
            <!-- Assets Card -->
            <div class="card">
                <h3>Assets</h3>
                <div class="inner-cards">
                    <?php if (!empty($assets)): ?>
                        <?php foreach ($assets as $asset): ?>
                            <div class="inner-card">
                                <div><b>Item:</b> <?php echo htmlspecialchars($asset['asset_name']); ?></div>
                                <div><b>Quantity:</b> <?php echo htmlspecialchars($asset['asset_quantity']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="inner-card">
                            <div><b>No assets available</b></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Request Log Card -->
            <div class="card">
                <h3>Request Log</h3>
                <div class="inner-cards">
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $request): ?>
                            <div class="inner-card">
                                <div><b>Requested by:</b> <?php echo htmlspecialchars($request['employee_name']); ?></div>
                                <div><b>Requested Item:</b> <?php echo htmlspecialchars($request['asset_name']); ?></div>
                                <div><b>Quantity:</b> <?php echo htmlspecialchars($request['quantity_requested']); ?></div>
                                <div><b>Department:</b> <?php echo htmlspecialchars($request['department_name'] ?? 'N/A'); ?></div>
                                <div><b>Date:</b> <?php echo htmlspecialchars(date('M d, Y', strtotime($request['request_date']))); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="inner-card">
                            <div><b>No requests available</b></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        let profileOpen = false;

        // Toggle Profile Panel
        function toggleProfile(event) {
            event.preventDefault();
            profileOpen = !profileOpen;
            
            if (profileOpen) {
                sidebar.classList.add('expanded');
            } else {
                sidebar.classList.remove('expanded');
            }
        }

        // Close profile when clicking outside
        document.addEventListener('click', function(event) {
            if (profileOpen && !sidebar.contains(event.target)) {
                sidebar.classList.remove('expanded');
                profileOpen = false;
            }
        });
    </script>

</body>
</html>