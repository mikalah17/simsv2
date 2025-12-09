<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('user');

require_once __DIR__ . '/../php/db_config.php';

$pdo = getPDO();
$assets = [];
$employees = [];
$requests = [];
$message = '';
$message_type = '';

// Get assets for dropdown
$stmt = $pdo->query('SELECT asset_id, asset_name, asset_quantity FROM asset ORDER BY asset_name');
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get employees for dropdown
$stmt = $pdo->query('SELECT employee_id, CONCAT(employee_fname, " ", employee_lname) as employee_name FROM employee ORDER BY employee_fname');
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_request') {
    try {
        $asset_id = intval($_POST['asset_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        $employee_id = intval($_POST['employee_id'] ?? 0);
        $request_date = $_POST['request_date'] ?? date('Y-m-d');
        
        if (!$asset_id || !$quantity || !$employee_id) {
            throw new Exception('All fields are required');
        }
        
        if ($quantity <= 0) {
            throw new Exception('Quantity must be greater than 0');
        }
        
        // Verify asset exists and has stock
        $asset = $pdo->prepare('SELECT asset_quantity FROM asset WHERE asset_id = ?');
        $asset->execute([$asset_id]);
        $assetData = $asset->fetch(PDO::FETCH_ASSOC);
        
        if (!$assetData) {
            throw new Exception('Asset not found');
        }
        
        // Insert request
        $pdo->prepare('INSERT INTO request (asset_id, quantity_requested, employee_id, request_date) VALUES (?, ?, ?, ?)')
            ->execute([$asset_id, $quantity, $employee_id, $request_date]);
        
        $message = 'Request logged successfully!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get user's requests
$stmt = $pdo->query('
    SELECT 
        r.request_id,
        a.asset_name,
        r.quantity_requested,
        e.employee_fname,
        e.employee_lname,
        d.department_name,
        r.request_date
    FROM request r
    JOIN asset a ON r.asset_id = a.asset_id
    JOIN employee e ON r.employee_id = e.employee_id
    LEFT JOIN dept d ON e.department_id = d.department_id
    ORDER BY r.request_date DESC
');
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .main-content h1 {
            color: white;
            margin: 0 0 25px 0;
            font-size: 36px;
        }

        /* Message alerts */
        .message {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            min-width: 300px;
        }

        .message.success {
            background: rgba(40, 167, 69, 0.95);
            color: white;
            border: 1px solid rgba(40, 167, 69, 1);
        }

        .message.error {
            background: rgba(220, 53, 69, 0.95);
            color: white;
            border: 1px solid rgba(220, 53, 69, 1);
        }

        /* Layout */
        .content-wrapper {
            display: flex;
            gap: 25px;
            height: calc(100vh - 120px);
        }

        /* LEFT PANEL */
        .request-panel {
            flex: 2;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(8px);
            padding: 25px;
            border-radius: 18px;
            color: white;
            display: flex;
            flex-direction: column;
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .search-bar {
            width: 200px;
            padding: 8px 12px;
            border-radius: 20px;
            border: none;
            outline: none;
        }

        .sort-btn {
            padding: 8px 15px;
            border-radius: 20px;
            background: rgba(255,255,255,0.8);
            border: none;
            font-weight: 600;
            cursor: pointer;
            color: #0F1B65;
            appearance: none;
        }

        .scroll-area {
            overflow-y: auto;
            padding-right: 10px;
        }

        .scroll-area::-webkit-scrollbar {
            width: 6px;
        }

        .scroll-area::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.5);
            border-radius: 5px;
        }

        .inner-card {
            background: rgba(255,255,255,0.85);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 12px;
            color: #0F1B65;
            box-shadow: 0px 3px 8px rgba(0,0,0,0.15);
        }

        .item-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        /* RIGHT PANEL */
        .right-panel {
            flex: 1;
            padding: 25px;
            color: white;
            text-align: center;
        }

        .right-panel h2 {
            margin-bottom: 30px;
        }

        .right-panel label {
            display: block;
            text-align: left;
            margin-left: 15px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .right-panel input,
        .right-panel select {
            width: 92%;
            padding: 10px 12px;
            border-radius: 12px;
            border: none;
            margin-bottom: 18px;
            outline: none;
            font-family: 'DM Sans', Arial, sans-serif;
        }

        .log-btn {
            width: 60%;
            padding: 10px 15px;
            border-radius: 15px;
            background: #0F1B65;
            border: none;
            color: white;
            font-weight: bold;
            cursor: pointer;
            display: block;
            margin: 10px auto 0 auto;
            font-family: 'DM Sans', Arial, sans-serif;
        }

        .log-btn:hover {
            background: #162897;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/user_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Request Log</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="content-wrapper">

            <!-- LEFT side - Request History -->
            <div class="request-panel">

                <div class="request-header">
                    <input type="text" class="search-bar" id="searchInput" placeholder="Search">

                    <select class="sort-btn" id="sortSelect">
                        <option value="date-desc">Newest First</option>
                        <option value="date-asc">Oldest First</option>
                        <option value="name-asc">Name A-Z</option>
                        <option value="name-desc">Name Z-A</option>
                        <option value="qty-asc">Quantity Low-High</option>
                        <option value="qty-desc">Quantity High-Low</option>
                    </select>
                </div>

                <div class="scroll-area" id="requestsList">
                    <?php if (!empty($requests)): ?>
                        <?php foreach ($requests as $req): ?>
                            <div class="inner-card" 
                                 data-name="<?php echo htmlspecialchars($req['employee_fname'] . ' ' . $req['employee_lname']); ?>" 
                                 data-item="<?php echo htmlspecialchars($req['asset_name']); ?>"
                                 data-qty="<?php echo $req['quantity_requested']; ?>"
                                 data-date="<?php echo $req['request_date']; ?>">
                                <div class="item-details">
                                    <span><b>Requested by:</b> <?php echo htmlspecialchars($req['employee_fname'] . ' ' . $req['employee_lname']); ?></span>
                                    <span><b>Requested Item:</b> <?php echo htmlspecialchars($req['asset_name']); ?></span>
                                    <span><b>Quantity:</b> <?php echo intval($req['quantity_requested']); ?></span>
                                    <span><b>Department:</b> <?php echo htmlspecialchars($req['department_name'] ?? 'N/A'); ?></span>
                                    <span><b>Date:</b> <?php echo date('m/d/Y', strtotime($req['request_date'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: rgba(255,255,255,0.7); text-align: center; padding: 20px;">No requests found</p>
                    <?php endif; ?>
                </div>

            </div>

            <!-- RIGHT side - Log Request Form -->
            <div class="right-panel">
                <h2>Log a Request</h2>

                <form method="POST">
                    <label>Requested By:</label>
                    <select name="employee_id" required>
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo $emp['employee_id']; ?>">
                                <?php echo htmlspecialchars($emp['employee_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Requested Item:</label>
                    <select name="asset_id" required>
                        <option value="">-- Select Asset --</option>
                        <?php foreach ($assets as $asset): ?>
                            <option value="<?php echo $asset['asset_id']; ?>">
                                <?php echo htmlspecialchars($asset['asset_name']); ?> 
                                (<?php echo $asset['asset_quantity']; ?> available)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Quantity:</label>
                    <input type="number" name="quantity" min="1" required>

                    <label>Date:</label>
                    <input type="date" name="request_date" value="<?php echo date('Y-m-d'); ?>" required>

                    <button type="submit" name="action" value="submit_request" class="log-btn">Log Request</button>
                </form>
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

        // Auto-hide message after 3 seconds
        const message = document.querySelector('.message');
        if (message) {
            setTimeout(() => {
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 300);
            }, 3000);
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.inner-card');
            
            cards.forEach(card => {
                const name = card.dataset.name.toLowerCase();
                const item = card.dataset.item.toLowerCase();
                if (name.includes(searchTerm) || item.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Sort functionality
        document.getElementById('sortSelect').addEventListener('change', function(e) {
            const sortBy = e.target.value;
            const container = document.getElementById('requestsList');
            const cards = Array.from(container.querySelectorAll('.inner-card'));
            
            cards.sort((a, b) => {
                const nameA = a.dataset.name.toLowerCase();
                const nameB = b.dataset.name.toLowerCase();
                const qtyA = parseInt(a.dataset.qty);
                const qtyB = parseInt(b.dataset.qty);
                const dateA = new Date(a.dataset.date);
                const dateB = new Date(b.dataset.date);
                
                switch(sortBy) {
                    case 'name-asc':
                        return nameA.localeCompare(nameB);
                    case 'name-desc':
                        return nameB.localeCompare(nameA);
                    case 'qty-asc':
                        return qtyA - qtyB;
                    case 'qty-desc':
                        return qtyB - qtyA;
                    case 'date-asc':
                        return dateA - dateB;
                    case 'date-desc':
                        return dateB - dateA;
                    default:
                        return 0;
                }
            });
            
            cards.forEach(card => container.appendChild(card));
        });
    </script>
</body>
</html>