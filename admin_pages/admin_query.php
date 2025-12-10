<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('admin');

require_once __DIR__ . '/../php/db_config.php';

$pdo = getPDO();
$queryResult = null;
$queryError = null;
$executedQuery = null;
$queryToExecute = null;
$columns = [];

// Create query table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS query (
        query_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        query_text LONGTEXT NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY (user_id)
    )");
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Query table creation error: " . $e->getMessage());
}

// Handle query execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $query = trim($_POST['query']);
    
    if (!empty($query)) {
        try {
            // Log the query to history
            $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
            
            // Get the next query_id
            $maxStmt = $pdo->query("SELECT MAX(query_id) as max_id FROM query");
            $maxResult = $maxStmt->fetch(PDO::FETCH_ASSOC);
            $nextId = ($maxResult['max_id'] ?? 0) + 1;
            
            $historyStmt = $pdo->prepare("INSERT INTO query (query_id, user_id, queryDesc, queryTime) VALUES (?, ?, ?, NOW())");
            $historyStmt->execute([$nextId, $user_id, $query]);
            
            // Log to audit table
            $queryType = strtoupper(substr(trim($query), 0, 6));
            logAudit($pdo, $user_id, 'EXECUTE_QUERY', 'query', $nextId, "Executed $queryType query");
            
            // Execute the query
            $stmt = $pdo->query($query);
            
            // Check if it's a SELECT query
            if ($stmt !== false && $stmt instanceof \PDOStatement) {
                $queryResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (count($queryResult) > 0) {
                    $columns = array_keys($queryResult[0]);
                } else {
                    $queryResult = [];
                    $columns = [];
                }
                $executedQuery = $query;
            } else {
                // For INSERT, UPDATE, DELETE queries
                $executedQuery = $query;
                $queryResult = ['affected_rows' => $pdo->lastInsertId()];
            }
        } catch (PDOException $e) {
            $queryError = "Error executing query: " . $e->getMessage();
        } catch (Exception $e) {
            $queryError = "Error: " . $e->getMessage();
        }
    }
}

// Handle requery from history
if (isset($_GET['requery'])) {
    $requery_id = intval($_GET['requery']);
    $historyStmt = $pdo->prepare("SELECT queryDesc FROM query WHERE query_id = ?");
    $historyStmt->execute([$requery_id]);
    $historyRecord = $historyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($historyRecord) {
        $queryToExecute = $historyRecord['queryDesc'];
    }
}

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

        /* Main Content */
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

        /* Query Panel */
        .query-panel {
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(8px);
            padding: 25px;
            border-radius: 18px;
            color: white;
        }

        .query-panel h2 {
            margin: 0 0 15px 0;
            font-size: 24px;
        }

        .query-editor {
            display: flex;
            background: rgba(255,255,255,0.95);
            border-radius: 12px;
            margin-bottom: 15px;
            overflow: hidden;
            min-height: 100px;
            max-height: 400px;
        }

        .line-numbers {
            background: #f0f0f0;
            color: #666;
            padding: 12px 10px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            border-right: 1px solid #ddd;
            overflow: hidden;
            user-select: none;
            text-align: right;
            min-width: 35px;
            white-space: pre;
        }

        .query-input {
            flex: 1;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            padding: 12px;
            border: none;
            outline: none;
            resize: none;
            overflow: auto;
            color: #333;
            line-height: 1.6;
            tab-size: 4;
            -moz-tab-size: 4;
        }

        .button-row {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .clear-btn, .submit-btn {
            padding: 10px 25px;
            border-radius: 12px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
        }

        .clear-btn {
            background: rgba(255,255,255,0.4);
            color: white;
        }

        .clear-btn:hover {
            background: rgba(255,255,255,0.6);
        }

        .submit-btn {
            background: rgba(15, 27, 101, 0.8);
            color: white;
        }

        .submit-btn:hover {
            background: rgba(15, 27, 101, 1);
        }

        .history-btn {
            padding: 10px 20px;
            border-radius: 12px;
            border: none;
            background: rgba(255,255,255,0.9);
            color: #0F1B65;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
            transition: 0.3s;
        }

        .history-btn:hover {
            background: white;
        }

        /* Results Panel */
        .results-panel {
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

        .results-panel h2 {
            margin: 0 0 15px 0;
            font-size: 24px;
        }

        .table-wrapper {
            flex: 1;
            overflow: auto;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        .results-table thead {
            background: rgba(15, 27, 101, 0.8);
            color: white;
            position: sticky;
            top: 0;
        }

        .results-table th {
            padding: 12px;
            text-align: left;
            font-weight: 700;
            border-right: 1px solid rgba(15, 27, 101, 0.3);
        }

        .results-table th:last-child {
            border-right: none;
        }

        .results-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            color: #333;
        }

        .results-table tbody tr:hover {
            background: #f5f5f5;
        }

        .no-results {
            text-align: center;
            color: #999;
            padding: 40px;
            font-style: italic;
        }

        .error-message {
            background: rgba(255, 76, 76, 0.9);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.9);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Query Analyzer</h1>

        <div class="content-wrapper">

            <!-- Query Input -->
            <div class="query-panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2 style="margin: 0;">SQL Query</h2>
                    <a href="admin_queryhistory.php" class="history-btn">History</a>
                </div>
                
                <form method="POST" id="queryForm">
                    <div class="query-editor">
                        <div class="line-numbers" id="lineNumbers">1</div>
                        <textarea class="query-input" id="queryInput" name="query" placeholder="Enter your SQL query here..."><?php echo isset($queryToExecute) ? htmlspecialchars($queryToExecute) : (isset($_POST['query']) ? htmlspecialchars($_POST['query']) : ''); ?></textarea>
                    </div>
                    <div class="button-row">
                        <button type="button" class="clear-btn" onclick="clearQuery()">Clear</button>
                        <button type="submit" class="submit-btn">Execute Query</button>
                    </div>
                </form>
            </div>

            <!-- Results -->
            <div class="results-panel">
                <h2>Results</h2>
                <?php if ($queryError): ?>
                    <div class="error-message"><?php echo htmlspecialchars($queryError); ?></div>
                <?php elseif ($executedQuery): ?>
                    <div class="success-message">Query executed successfully!</div>
                    <div class="table-wrapper">
                        <?php if (is_array($queryResult) && count($queryResult) > 0): ?>
                            <table class="results-table">
                                <thead>
                                    <tr>
                                        <?php foreach ($columns as $column): ?>
                                            <th><?php echo htmlspecialchars($column); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($queryResult as $row): ?>
                                        <tr>
                                            <?php foreach ($columns as $column): ?>
                                                <td><?php echo htmlspecialchars($row[$column] ?? ''); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-results">Query executed but returned no results or modified data.</div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">Run a query to see results here</div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        const queryInput = document.getElementById('queryInput');
        const lineNumbers = document.getElementById('lineNumbers');
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

        // Update line numbers as user types
        function updateLineNumbers() {
            const lines = queryInput.value.split('\n').length;
            let numberString = '';
            for (let i = 1; i <= lines; i++) {
                numberString += i + '\n';
            }
            lineNumbers.textContent = numberString;
            
            // Auto-expand textarea height
            queryInput.style.height = 'auto';
            const scrollHeight = queryInput.scrollHeight;
            queryInput.style.height = Math.min(scrollHeight, 400) + 'px';
        }

        queryInput.addEventListener('input', updateLineNumbers);
        queryInput.addEventListener('scroll', function() {
            lineNumbers.scrollTop = queryInput.scrollTop;
        });

        // Initialize line numbers
        updateLineNumbers();

        function clearQuery() {
            queryInput.value = '';
            updateLineNumbers();
        }
    </script>

</body>
</html>
