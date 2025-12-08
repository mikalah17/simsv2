<?php
// Shared admin sidebar include
?>
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
        <div class="profile-name">FName<br>LName</div>
        <div class="profile-email">email@gmail.com</div>
        <button class="profile-logout">
            <img src="../image/logout.png">
            Log out
        </button>
    </div>
</div>
