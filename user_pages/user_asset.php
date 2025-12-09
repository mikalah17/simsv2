<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('user');

require_once __DIR__ . '/../php/db_config.php';

$pdo = getPDO();
$assets = [];
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                $name = trim($_POST['asset_name'] ?? '');
                $quantity = intval($_POST['asset_quantity'] ?? 0);
                
                if (!$name || $quantity < 0) {
                    throw new Exception('Invalid asset name or quantity');
                }
                
                $pdo->prepare('INSERT INTO asset (asset_name, asset_quantity) VALUES (?, ?)')
                    ->execute([$name, $quantity]);
                $message = 'Asset added successfully!';
                $message_type = 'success';
            }
            
            elseif ($_POST['action'] === 'update') {
                $asset_id = intval($_POST['asset_id'] ?? 0);
                $quantity = intval($_POST['asset_quantity'] ?? 0);
                
                if (!$asset_id || $quantity < 0) {
                    throw new Exception('Invalid asset ID or quantity');
                }
                
                $pdo->prepare('UPDATE asset SET asset_quantity = ? WHERE asset_id = ?')
                    ->execute([$quantity, $asset_id]);
                $message = 'Asset updated successfully!';
                $message_type = 'success';
            }
            
            elseif ($_POST['action'] === 'delete') {
                $asset_id = intval($_POST['asset_id'] ?? 0);
                
                if (!$asset_id) {
                    throw new Exception('Invalid asset ID');
                }
                
                // Check if asset has requests
                $check = $pdo->prepare('SELECT COUNT(*) as count FROM request WHERE asset_id = ?');
                $check->execute([$asset_id]);
                $result = $check->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    throw new Exception('Cannot delete asset with existing requests');
                }
                
                $pdo->prepare('DELETE FROM asset WHERE asset_id = ?')->execute([$asset_id]);
                $message = 'Asset deleted successfully!';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get all assets
$stmt = $pdo->query('SELECT asset_id, asset_name, asset_quantity FROM asset ORDER BY asset_name');
$assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background-color: #1a1a2e;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100vh;
            font-family: 'DM Sans', Arial, sans-serif;
            background-image: url("../image/sims_bg.png");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

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

        .sidebar.expanded .nav-links {
            opacity: 0;
            pointer-events: none;
        }

        .sidebar.expanded .logo {
            opacity: 0;
            pointer-events: none;
        }

        .sidebar.expanded .profile-panel {
            opacity: 1;
            pointer-events: all;
        }

        .main-content {
            margin-left: 220px;
            padding: 40px;
            min-height: 100vh;
            box-sizing: border-box;
            padding-bottom: 80px;
            overflow-y: auto;
            transition: margin-left 0.3s ease;
        }

        .sidebar.expanded ~ .main-content {
            margin-left: 350px;
        }

        .main-content h1 {
            color: white;
            margin-top: 0;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            color: white;
            margin-bottom: 30px;
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 24px;
            color: white;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: white;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            background: rgba(255,255,255,0.9);
            color: #0F1B65;
            box-sizing: border-box;
            font-family: 'DM Sans', Arial, sans-serif;
            font-size: 14px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #0F1B65;
            background: white;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'DM Sans', Arial, sans-serif;
            font-size: 14px;
            transition: 0.3s;
        }

        .btn-primary {
            background: rgba(15, 27, 101, 0.8);
            color: white;
        }

        .btn-primary:hover {
            background: rgba(15, 27, 101, 1);
        }

        .btn-danger {
            background: rgba(220, 53, 69, 0.8);
            color: white;
        }

        .btn-danger:hover {
            background: rgba(220, 53, 69, 1);
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: rgba(40, 167, 69, 0.3);
            color: #98fb98;
            border: 1px solid rgba(40, 167, 69, 0.5);
        }

        .message.error {
            background: rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.5);
        }

        .assets-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .assets-table thead {
            background: rgba(15, 27, 101, 0.2);
        }

        .assets-table th {
            padding: 12px;
            text-align: left;
            color: white;
            font-weight: 600;
            border-bottom: 2px solid rgba(255,255,255,0.2);
        }

        .assets-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: white;
        }

        .assets-table tbody tr:hover {
            background: rgba(255,255,255,0.05);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .nav-links {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
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

        .sidebar a .icon {
            width: 30px;
            height: 30px;
            margin-right: 12px;
        }

        .sidebar a:hover {
            background: rgba(15, 27, 101, 0.67);
        }

        .sidebar a.active {
            background: rgba(15, 27, 101, 0.8);
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

        .sidebar .logo {
            width: 250px;
            margin-bottom: 10px;
        }

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
            margin-bottom: 0;
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
    </style>
</head>
<body>

    <?php include __DIR__ . '/user_sidebar.php'; ?>

    <div class="main-content">
        <h1>Asset Management</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Add New Asset Form -->
        <div class="card">
            <h2>Add New Asset</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="asset_name">Asset Name:</label>
                    <input type="text" id="asset_name" name="asset_name" required>
                </div>
                <div class="form-group">
                    <label for="asset_quantity">Initial Quantity:</label>
                    <input type="number" id="asset_quantity" name="asset_quantity" min="0" required>
                </div>
                <div class="button-group">
                    <button type="submit" class="btn btn-primary" name="action" value="add">Add Asset</button>
                </div>
            </form>
        </div>

        <!-- Assets List -->
        <div class="card">
            <h2>All Assets (<?php echo count($assets); ?>)</h2>
            <?php if (!empty($assets)): ?>
                <table class="assets-table">
                    <thead>
                        <tr>
                            <th>Asset Name</th>
                            <th>Quantity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                <td><?php echo intval($asset['asset_quantity']); ?> units</td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="asset_id" value="<?php echo $asset['asset_id']; ?>">
                                            <input type="hidden" name="asset_quantity" value="">
                                            <button type="button" class="btn btn-small btn-primary" onclick="editAsset(<?php echo $asset['asset_id']; ?>, '<?php echo htmlspecialchars($asset['asset_name']); ?>', <?php echo $asset['asset_quantity']; ?>)">Edit</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="asset_id" value="<?php echo $asset['asset_id']; ?>">
                                            <button type="submit" class="btn btn-small btn-danger" name="action" value="delete" onclick="return confirm('Delete this asset?');">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #999; text-align: center; padding: 20px;">No assets found</p>
            <?php endif; ?>
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

        function editAsset(assetId, name, quantity) {
            const newQty = prompt('Edit quantity for "' + name + '":\n\nCurrent quantity: ' + quantity, quantity);
            if (newQty !== null && newQty !== '') {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="asset_id" value="${assetId}">
                    <input type="hidden" name="asset_quantity" value="${newQty}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

</body>
</html>
