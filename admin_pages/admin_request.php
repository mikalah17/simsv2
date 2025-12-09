<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('admin');

require_once __DIR__ . '/../php/db_config.php';

$pdo = getPDO();

// Get all requests with employee and asset information
$requestStmt = $pdo->query('
    SELECT 
        r.request_id,
        r.quantity_requested,
        r.request_date,
        e.employee_fname,
        e.employee_lname,
        e.employee_mname,
        d.department_name,
        a.asset_name
    FROM request r
    LEFT JOIN employee e ON r.employee_id = e.employee_id
    LEFT JOIN dept d ON e.department_id = d.department_id
    LEFT JOIN asset a ON r.asset_id = a.asset_id
    ORDER BY r.request_date DESC
');
$requests = $requestStmt->fetchAll(PDO::FETCH_ASSOC);

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

        /* Request Log Panel */
        .request-log-panel {
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

        .log-header {
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

        .request-card {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            color: #0F1B65;
            box-shadow: 0px 3px 8px rgba(0,0,0,0.15);
            transition: 0.3s;
        }

        .request-card:hover {
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .request-header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .requested-by {
            font-size: 18px;
            font-weight: 700;
            color: #0F1B65;
        }

        .request-date {
            font-size: 14px;
            color: #666;
        }

        .request-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detail-label {
            font-size: 12px;
            color: #999;
            font-weight: 700;
            text-transform: uppercase;
        }

        .detail-value {
            font-size: 16px;
            color: #0F1B65;
            font-weight: 700;
        }

        .no-requests {
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
        <h1>Request Log</h1>

        <div class="content-wrapper">

            <!-- Request Log Panel -->
            <div class="request-log-panel">
                <div class="log-header">
                    <div class="header-controls">
                        <input type="text" class="search-bar" id="searchBar" placeholder="Search" onkeyup="searchRequests()">
                        <select class="sort-btn" id="sortSelect" onchange="sortRequests()">
                            <option value="date-recent">Date (Most Recent)</option>
                            <option value="date-oldest">Date (Oldest)</option>
                            <option value="name-asc">Requested By (A-Z)</option>
                            <option value="name-desc">Requested By (Z-A)</option>
                            <option value="quantity-high">Quantity (High to Low)</option>
                            <option value="quantity-low">Quantity (Low to High)</option>
                        </select>
                    </div>
                </div>

                <div class="scroll-area" id="requestsContainer">
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $request): ?>
                            <div class="request-card" data-date="<?php echo htmlspecialchars($request['request_date']); ?>" data-name="<?php echo htmlspecialchars($request['employee_fname'] . ' ' . $request['employee_lname']); ?>" data-quantity="<?php echo $request['quantity_requested']; ?>">
                                <div class="request-header-info">
                                    <div class="requested-by"><?php echo htmlspecialchars($request['employee_fname'] . ' ' . $request['employee_lname']); ?></div>
                                    <div class="request-date"><?php echo htmlspecialchars(date('F d, Y', strtotime($request['request_date']))); ?></div>
                                </div>
                                <div class="request-details">
                                    <div class="detail-item">
                                        <div class="detail-label">REQUESTED ITEM</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($request['asset_name'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">QUANTITY</div>
                                        <div class="detail-value"><?php echo $request['quantity_requested']; ?></div>
                                    </div>
                                    <div class="detail-item">
                                        <div class="detail-label">DEPARTMENT</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($request['department_name'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-requests">No requests found in the database</div>
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

        // Search Requests
        function searchRequests() {
            const searchBar = document.getElementById('searchBar');
            const filter = searchBar.value.toUpperCase();
            const cards = document.querySelectorAll('.request-card');

            cards.forEach(card => {
                const requestName = card.getAttribute('data-name').toUpperCase();
                card.style.display = requestName.includes(filter) ? '' : 'none';
            });
        }

        // Sort Requests
        function sortRequests() {
            const sortSelect = document.getElementById('sortSelect');
            const container = document.getElementById('requestsContainer');
            const cards = Array.from(document.querySelectorAll('.request-card'));

            cards.sort((a, b) => {
                const dateA = new Date(a.getAttribute('data-date'));
                const dateB = new Date(b.getAttribute('data-date'));
                const nameA = a.getAttribute('data-name').toLowerCase();
                const nameB = b.getAttribute('data-name').toLowerCase();
                const qtyA = parseInt(a.getAttribute('data-quantity'));
                const qtyB = parseInt(b.getAttribute('data-quantity'));

                switch (sortSelect.value) {
                    case 'date-recent':
                        return dateB - dateA;
                    case 'date-oldest':
                        return dateA - dateB;
                    case 'name-asc':
                        return nameA.localeCompare(nameB);
                    case 'name-desc':
                        return nameB.localeCompare(nameA);
                    case 'quantity-high':
                        return qtyB - qtyA;
                    case 'quantity-low':
                        return qtyA - qtyB;
                    default:
                        return 0;
                }
            });

            cards.forEach(card => container.appendChild(card));
        }
    </script>

</body>
</html>
