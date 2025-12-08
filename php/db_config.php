<?php
// Database configuration and PDO helper
// Replace the placeholder values with your actual DB credentials
$DB_HOST = '127.0.0.1';
$DB_NAME = 'sims';
$DB_USER = 'root';
$DB_PASS = '';
$DB_PORT = 3306;

function getPDO()
{
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $DB_PORT;
    // Helpful checks for local development
    if (!extension_loaded('pdo')) {
        error_log('PDO extension is not loaded');
        die('PHP PDO extension not available. Please enable PDO in php.ini.');
    }
    if (!extension_loaded('pdo_mysql')) {
        error_log('PDO MySQL driver (pdo_mysql) is not enabled');
        die('PHP pdo_mysql extension not available. Please enable pdo_mysql in php.ini.');
    }

    $hostsToTry = [$DB_HOST];
    if ($DB_HOST === '127.0.0.1') {
        // Try the hostname variant as well (some MySQL setups treat them differently)
        $hostsToTry[] = 'localhost';
    } elseif ($DB_HOST === 'localhost') {
        $hostsToTry[] = '127.0.0.1';
    }

    $lastEx = null;
    foreach ($hostsToTry as $host) {
        $dsn = "mysql:host={$host};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            $lastEx = $e;
            // try next host
        }
    }

    // If we reach here, all attempts failed. Log the detailed exception.
    if ($lastEx) {
        error_log('DB connection failed: ' . $lastEx->getMessage());
    } else {
        error_log('DB connection failed: unknown reason');
    }

    // For local development, reveal the PDO error message to help debugging.
    $isLocal = (PHP_SAPI === 'cli') || (isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['REMOTE_ADDR'] === '::1'));
    if ($isLocal && $lastEx) {
        // Show detailed message only for local requests or CLI
        http_response_code(500);
        die('Database connection failed: ' . $lastEx->getMessage());
    }

    http_response_code(500);
    die('Database connection failed. Check DB credentials and that MySQL is running.');
}

?>
