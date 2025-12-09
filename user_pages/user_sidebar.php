<?php
// Shared user sidebar include
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
        <div class="profile-name">FName<br>LName</div>
        <div class="profile-email">email@gmail.com</div>
        <button class="profile-logout">
            <img src="../image/logout.png"> Log out
        </button>
    </div>
</div>
