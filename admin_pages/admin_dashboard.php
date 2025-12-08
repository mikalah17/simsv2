<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('admin');
// Get session data for display
$userName = htmlspecialchars($_SESSION['name'] ?? 'User');
$userEmail = htmlspecialchars($_SESSION['email'] ?? '');
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
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            transition: width 0.3s ease;
            z-index: 1000;
        }

        .sidebar.expanded {
            width: 350px;
        }

        .logo {
            width: 150px;
            height: auto;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: auto;
            width: 100%;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            width: 100%;
            max-width: 180px;
            transition: 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .icon {
            width: 25px;
            height: 25px;
        }

        .logout {
            margin-bottom: 20px;
            color: #ff6b6b;
        }

        .logout:hover {
            background: rgba(255, 107, 107, 0.1);
        }

        /* Profile Panel */
        .profile-panel {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 280px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            color: white;
            display: none;
            z-index: 2000;
        }

        .sidebar.expanded ~ .profile-panel {
            display: block;
        }

        .profile-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .profile-email {
            font-size: 13px;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.8);
            word-break: break-all;
        }

        .profile-logout {
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
            padding: 10px 15px;
            width: 100%;
            justify-content: center;
        }

        .profile-logout:hover {
            background: rgba(15, 27, 101, 0.85);
        }

        .profile-logout img {
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

        .sidebar.expanded .logout {
            opacity: 0;
            pointer-events: none;
        }

        .main-content h1 {
            color: white;
            margin-bottom: 30px;
            font-size: 32px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            height: calc(100% - 80px);
            overflow-y: auto;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .card h3 {
            color: white;
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 700;
        }

        .inner-cards {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .inner-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }

        .inner-card div {
            margin: 5px 0;
        }

        .inner-card b {
            color: white;
        }

        @media (max-width: 1024px) {
            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <img src="../image/sims_logo.png" class="logo">

        <!-- Navigation Links -->
        <div class="nav-links">
            <a href="#" onclick="toggleProfile(event)"><img src="../image/profile.png" class="icon">Profile</a>
            <a href="admin_dashboard.php"><img src="../image/user_dashboard.png" class="icon">Dashboard</a>
            <a href="admin_employees.php"><img src="../image/employees.png" class="icon">Employees</a>
            <a href="admin_departments.php"><img src="../image/departments.png" class="icon">Departments</a>
            <a href="admin_assets.php"><img src="../image/assets.png" class="icon">Assets</a>
            <a href="admin_request.php"><img src="../image/requests.png" class="icon">Request Log</a>  
            <a href="admin_audit.php"><img src="../image/audit.png" class="icon">Audit</a>
            <a href="admin_query.php"><img src="../image/query.png" class="icon">Query Analyzer</a>
        </div>

        <a href="../php/logout.php" class="logout"><img src="../image/logout.png" class="icon">Log Out</a>
        
        <!-- Profile Panel (Hidden by default) -->
        <div class="profile-panel">
            <div class="profile-name"><?php echo str_replace(' ', '<br>', $userName); ?></div>
            <div class="profile-email"><?php echo $userEmail; ?></div>
            <a href="../php/logout.php" class="profile-logout" style="text-decoration: none;">
                <img src="../image/logout.png">
                Log out
            </a>
        </div>
    </div>

    <!-- Main content -->
    <div class="main-content">
        <h1>Admin Dashboard</h1>

        <div class="cards">
            <!-- Employees Card -->
            <div class="card">
                <h3>Employees</h3>
                <div class="inner-cards">
                    <div class="inner-card">
                        <div><b>Employee Name:</b></div>
                        <div>View and manage employee records</div>
                    </div>
                </div>
            </div>

            <!-- Departments Card -->
            <div class="card">
                <h3>Departments</h3>
                <div class="inner-cards">
                    <div class="inner-card">
                        <div><b>Department Info:</b></div>
                        <div>Manage department information</div>
                    </div>
                </div>
            </div>

            <!-- Assets Card -->
            <div class="card">
                <h3>Assets</h3>
                <div class="inner-cards">
                    <div class="inner-card">
                        <div><b>Asset Inventory:</b></div>
                        <div>Track and manage assets</div>
                    </div>
                </div>
            </div>

            <!-- Requests Card -->
            <div class="card">
                <h3>Requests</h3>
                <div class="inner-cards">
                    <div class="inner-card">
                        <div><b>Request Log:</b></div>
                        <div>Monitor all requests</div>
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
    </script>

</body>
</html>
