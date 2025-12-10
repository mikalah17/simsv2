<?php
// Shared user sidebar include
// Note: session_start() is called in auth_check.php before this file is included

// Get user name from session
$firstName = isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'First';
$lastName = isset($_SESSION['last_name']) ? htmlspecialchars($_SESSION['last_name']) : 'Last';
$email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'email@gmail.com';
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <img src="../image/sims_logo.png" class="logo">

    <!-- Navigation Links -->
    <div class="nav-links">
        <a href="#" onclick="toggleProfile(event)"><img src="../image/profile.png" class="icon">Profile</a>
        <a href="user_dashboard.php"><img src="../image/user_dashboard.png" class="icon">Dashboard</a>
        <a href="user_asset.php"><img src="../image/assets.png" class="icon">Assets</a>
        <a href="user_request.php"><img src="../image/requests.png" class="icon">Request Log</a>
        <a href="user_report.php"><img src="../image/report.png" class="icon">Report</a>
    </div>

    <a href="../php/logout.php" class="logout"><img src="../image/logout.png" class="icon">Log Out</a>

    <!-- Profile Panel -->
    <div class="profile-panel">
        <div class="profile-name"><?php echo $firstName; ?><br><?php echo $lastName; ?></div>
        <div class="profile-email"><?php echo $email; ?></div>
        <div class="profile-buttons">
            <button class="profile-back" onclick="toggleProfile(event)">
                <img src="../image/logout.png" style="transform: rotate(180deg);">
                Back
            </button>
            <button class="profile-logout" onclick="window.location.href='../php/logout.php';">
                <img src="../image/logout.png">
                Log out
            </button>
        </div>
    </div>
</div>
