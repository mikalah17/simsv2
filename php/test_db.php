<?php
// Simple DB diagnostic for local debugging with Laragon
require_once __DIR__ . '/db_config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "PHP version: " . PHP_VERSION . "\n";
echo "SAPI: " . PHP_SAPI . "\n";
echo "PDO extension: " . (extension_loaded('pdo') ? 'loaded' : 'missing') . "\n";
echo "PDO MySQL driver: " . (extension_loaded('pdo_mysql') ? 'loaded' : 'missing') . "\n";

echo "Attempting direct PDO connection using values from db_config.php...\n";

$dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
echo "DSN: " . $dsn . "\n";
echo "User: " . $DB_USER . "\n";

try {
    // Try to connect directly (this will throw if it fails)
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Connection: OK\n";
    // Show a quick query result count for users table if present
    try {
        $r = $pdo->query('SELECT COUNT(*) AS c FROM users');
        $count = $r->fetch(PDO::FETCH_ASSOC)['c'];
        echo "users table count: " . $count . "\n";
    } catch (Exception $e) {
        echo "Note: could not query users table: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nDiagnostic complete. If 'pdo_mysql' is missing, open Laragon -> PHP -> php.ini and enable the extension 'pdo_mysql', then restart Apache/Laragon.\n";

?>

<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=sims;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "CONNECTED OK\n";
} catch (PDOException $e) {
    http_response_code(500);
    echo "PDO ERROR: " . $e->getMessage();
}
