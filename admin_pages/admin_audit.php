<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('admin');

require_once __DIR__ . '/../php/db_config.php';

$pdo = getPDO();

// Get all audit logs with user information
$auditStmt = $pdo->query('
    SELECT 
        a.audit_id,
        a.user_id,
        u.first_name,
        u.last_name,
        a.actionType,
        a.tableAffected,
        a.record_id,
        a.action_desc,
        a.actionTime
    FROM audit a
    LEFT JOIN users u ON a.user_id = u.user_id
    ORDER BY a.actionTime DESC
');
$auditLogs = $auditStmt->fetchAll(PDO::FETCH_ASSOC);

// Get user info for session
$firstName = isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'First';
$lastName = isset($_SESSION['last_name']) ? htmlspecialchars($_SESSION['last_name']) : 'Last';
$email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'email@gmail.com';
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
            background: rgba(15, 27, 101, 0.67);
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
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
            overflow: hidden;
        }

        .sidebar.expanded ~ .main-content {
            margin-left: 350px;
        }

        .main-content h1 {
            color: white;
            margin: 0 0 25px 0;
            font-size: 36px;
        }

        .content-wrapper {
            display: flex;
            flex-direction: column;
            gap: 25px;
            flex: 1;
            overflow: hidden;
        }

        /* Audit Table Panel */
        .audit-panel {
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(8px);
            padding: 25px;
            border-radius: 18px;
            color: white;
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .audit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-bar {
            width: 250px;
            padding: 10px 15px;
            border-radius: 20px;
            border: none;
            outline: none;
            font-size: 14px;
        }

        .sort-btn {
            padding: 10px 20px;
            border-radius: 20px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            color: #0F1B65;
            background: white;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            font-size: 14px;
        }

        .scroll-area {
            overflow-y: auto;
            padding-right: 10px;
            flex: 1;
        }

        .scroll-area::-webkit-scrollbar {
            width: 8px;
        }

        .scroll-area::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 5px;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255,255,255,0.95);
            border-radius: 12px;
            overflow: hidden;
            color: #0F1B65;
        }

        .audit-table thead {
            background: rgba(15, 27, 101, 0.8);
            color: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .audit-table thead th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            border-right: 1px solid rgba(0,0,0,0.1);
        }

        .audit-table thead th:last-child {
            border-right: none;
        }

        .audit-table tbody tr {
            border-bottom: 1px solid #e0e0e0;
            transition: 0.2s;
        }

        .audit-table tbody tr:hover {
            background: #f5f5f5;
        }

        .audit-table tbody td {
            padding: 12px;
            font-size: 13px;
        }

        .action-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
        }

        .action-insert {
            background: #51cf66;
            color: white;
        }

        .action-update {
            background: #4dabf7;
            color: white;
        }

        .action-delete {
            background: #ff6b6b;
            color: white;
        }

        .action-execute-query {
            background: #ffd43b;
            color: #333;
        }

        .no-records {
            text-align: center;
            color: rgba(255,255,255,0.7);
            padding: 40px;
            font-style: italic;
            font-size: 18px;
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Audit Log</h1>

        <div class="content-wrapper">

            <!-- Audit Log Panel -->
            <div class="audit-panel">
                <div class="audit-header">
                    <div class="header-controls">
                        <input type="text" class="search-bar" id="searchBar" placeholder="Search by user or action..." onkeyup="searchAudit()">
                        <select class="sort-btn" id="sortSelect" onchange="sortAudit()">
                            <option value="time-newest">Time (Newest First)</option>
                            <option value="time-oldest">Time (Oldest First)</option>
                            <option value="user-asc">User (A-Z)</option>
                            <option value="user-desc">User (Z-A)</option>
                            <option value="action-asc">Action Type (A-Z)</option>
                            <option value="action-desc">Action Type (Z-A)</option>
                            <option value="table-asc">Table (A-Z)</option>
                            <option value="table-desc">Table (Z-A)</option>
                            <option value="id-asc">Audit ID (Low to High)</option>
                            <option value="id-desc">Audit ID (High to Low)</option>
                        </select>
                    </div>
                </div>

                <div class="scroll-area">
                    <?php if (count($auditLogs) > 0): ?>
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th>Audit ID</th>
                                    <th>User</th>
                                    <th>Action Type</th>
                                    <th>Table</th>
                                    <th>Record ID</th>
                                    <th>Description</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="auditTableBody">
                                <?php foreach ($auditLogs as $log): ?>
                                    <tr data-user="<?php echo htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')); ?>" data-action="<?php echo htmlspecialchars($log['actionType']); ?>" data-desc="<?php echo htmlspecialchars($log['action_desc']); ?>" data-table="<?php echo htmlspecialchars($log['tableAffected']); ?>" data-id="<?php echo intval($log['audit_id']); ?>" data-time="<?php echo strtotime($log['actionTime']); ?>">
                                        <td><?php echo htmlspecialchars($log['audit_id']); ?></td>
                                        <td><?php echo htmlspecialchars(($log['first_name'] ?? 'Unknown') . ' ' . ($log['last_name'] ?? '')); ?></td>
                                        <td>
                                            <span class="action-badge action-<?php echo strtolower($log['actionType']); ?>">
                                                <?php echo htmlspecialchars($log['actionType']); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($log['tableAffected']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($log['record_id']); ?></td>
                                        <td><?php echo htmlspecialchars($log['action_desc']); ?></td>
                                        <td><?php echo htmlspecialchars(date('F d, Y H:i:s', strtotime($log['actionTime']))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-records">No audit logs found</div>
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

        // Search Audit Logs
        function searchAudit() {
            const searchBar = document.getElementById('searchBar');
            const filter = searchBar.value.toUpperCase();
            const rows = document.querySelectorAll('#auditTableBody tr');

            rows.forEach(row => {
                const user = row.getAttribute('data-user').toUpperCase();
                const action = row.getAttribute('data-action').toUpperCase();
                const desc = row.getAttribute('data-desc').toUpperCase();
                
                if (user.includes(filter) || action.includes(filter) || desc.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Sort Audit Logs
        function sortAudit() {
            const sortSelect = document.getElementById('sortSelect');
            const tbody = document.getElementById('auditTableBody');
            const rows = Array.from(document.querySelectorAll('#auditTableBody tr'));

            rows.sort((a, b) => {
                const sortBy = sortSelect.value;
                let compareA, compareB;

                switch(sortBy) {
                    case 'time-newest':
                        compareA = parseInt(b.getAttribute('data-time'));
                        compareB = parseInt(a.getAttribute('data-time'));
                        return compareA - compareB;
                    case 'time-oldest':
                        compareA = parseInt(a.getAttribute('data-time'));
                        compareB = parseInt(b.getAttribute('data-time'));
                        return compareA - compareB;
                    case 'user-asc':
                        compareA = a.getAttribute('data-user').toLowerCase();
                        compareB = b.getAttribute('data-user').toLowerCase();
                        return compareA.localeCompare(compareB);
                    case 'user-desc':
                        compareA = b.getAttribute('data-user').toLowerCase();
                        compareB = a.getAttribute('data-user').toLowerCase();
                        return compareA.localeCompare(compareB);
                    case 'action-asc':
                        compareA = a.getAttribute('data-action').toLowerCase();
                        compareB = b.getAttribute('data-action').toLowerCase();
                        return compareA.localeCompare(compareB);
                    case 'action-desc':
                        compareA = b.getAttribute('data-action').toLowerCase();
                        compareB = a.getAttribute('data-action').toLowerCase();
                        return compareA.localeCompare(compareB);
                    case 'table-asc':
                        compareA = a.getAttribute('data-table').toLowerCase();
                        compareB = b.getAttribute('data-table').toLowerCase();
                        return compareA.localeCompare(compareB);
                    case 'table-desc':
                        compareA = b.getAttribute('data-table').toLowerCase();
                        compareB = a.getAttribute('data-table').toLowerCase();
                        return compareA.localeCompare(compareB);
                    case 'id-asc':
                        compareA = parseInt(a.getAttribute('data-id'));
                        compareB = parseInt(b.getAttribute('data-id'));
                        return compareA - compareB;
                    case 'id-desc':
                        compareA = parseInt(b.getAttribute('data-id'));
                        compareB = parseInt(a.getAttribute('data-id'));
                        return compareA - compareB;
                    default:
                        return 0;
                }
            });

            rows.forEach(row => tbody.appendChild(row));
        }
    </script>

</body>
</html>
