<?php
// Simple diagnostic page to check PDO and basic DB connectivity
require_once __DIR__ . '/db_config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>DB Diagnostic</h2>\n";

// Check PDO extension
echo '<p><strong>PDO extension:</strong> ' . (extension_loaded('pdo') ? 'loaded' : 'MISSING') . "</p>\n";
echo '<p><strong>PDO MySQL driver:</strong> ' . (extension_loaded('pdo_mysql') ? 'loaded' : 'MISSING') . "</p>\n";

try {
    $pdo = getPDO();
    echo '<p><strong>Connection:</strong> OK</p>';

    // Try a basic query against `users` if the table exists
    try {
        $stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM users');
        $row = $stmt->fetch();
        $count = $row['cnt'] ?? 'unknown';
        echo '<p><strong>`users` table row count:</strong> ' . htmlspecialchars($count) . '</p>';
    } catch (Exception $e) {
        echo '<p><strong>Users query:</strong> failed — ' . htmlspecialchars($e->getMessage()) . '</p>';
    }

} catch (Exception $e) {
    echo '<p><strong>Connection:</strong> failed — check DB credentials and pdo_mysql extension.</p>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}

echo '<p>Note: do not expose this file on production systems.</p>';

?>
