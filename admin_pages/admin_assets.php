<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('admin');

require_once __DIR__ . '/../php/db_config.php';

$pdo = getPDO();

// Get all assets
$assetStmt = $pdo->query('SELECT asset_id, asset_name, asset_quantity FROM asset ORDER BY asset_name');
$assets = $assetStmt->fetchAll(PDO::FETCH_ASSOC);
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

        /* Main Page Layout */
        .main-content {
            margin-left: 220px;
            padding: 40px;
            height: 100vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
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

        .content-wrapper {
            display: flex;
            flex-direction: column;
            gap: 25px;
            flex: 1;
            overflow: hidden;
        }

        /* Assets Panel */
        .assets-panel {
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(8px);
            padding: 25px;
            border-radius: 18px;
            color: white;
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
        }

        .assets-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .assets-header h2 {
            margin: 0;
            font-size: 24px;
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

        .asset-card {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            color: #0F1B65;
            box-shadow: 0px 3px 8px rgba(0,0,0,0.15);
            transition: 0.3s;
        }

        .asset-card:hover {
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .asset-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .asset-quantity {
            font-size: 15px;
            color: #666;
        }

        .no-assets {
            text-align: center;
            color: rgba(255,255,255,0.7);
            padding: 40px;
            font-style: italic;
            font-size: 18px;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Assets</h1>

        <div class="content-wrapper">

            <!-- Assets Panel -->
            <div class="assets-panel">
                <div class="assets-header">
                    <div class="header-controls">
                        <input type="text" class="search-bar" id="searchBar" placeholder="Search" onkeyup="searchAssets()">
                        <select class="sort-btn" id="sortSelect" onchange="sortAssets()">
                            <option value="name-asc">Name (A-Z)</option>
                            <option value="name-desc">Name (Z-A)</option>
                            <option value="quantity-high">Quantity (High to Low)</option>
                            <option value="quantity-low">Quantity (Low to High)</option>
                        </select>
                    </div>
                </div>

                <div class="scroll-area" id="assetsContainer">
                    <?php if (count($assets) > 0): ?>
                        <?php foreach ($assets as $asset): ?>
                            <div class="asset-card" data-name="<?php echo htmlspecialchars($asset['asset_name']); ?>" data-quantity="<?php echo $asset['asset_quantity']; ?>">
                                <div class="asset-name"><?php echo htmlspecialchars($asset['asset_name']); ?></div>
                                <div class="asset-quantity">Quantity: <?php echo $asset['asset_quantity']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-assets">No assets found</div>
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

        // Search Assets
        function searchAssets() {
            const searchBar = document.getElementById('searchBar');
            const filter = searchBar.value.toUpperCase();
            const cards = document.querySelectorAll('.asset-card');

            cards.forEach(card => {
                const assetName = card.getAttribute('data-name').toUpperCase();
                card.style.display = assetName.includes(filter) ? '' : 'none';
            });
        }

        // Sort Assets
        function sortAssets() {
            const sortSelect = document.getElementById('sortSelect');
            const container = document.getElementById('assetsContainer');
            const cards = Array.from(document.querySelectorAll('.asset-card'));

            cards.sort((a, b) => {
                const nameA = a.getAttribute('data-name').toLowerCase();
                const nameB = b.getAttribute('data-name').toLowerCase();
                const qtyA = parseInt(a.getAttribute('data-quantity'));
                const qtyB = parseInt(b.getAttribute('data-quantity'));

                switch (sortSelect.value) {
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
