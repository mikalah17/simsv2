<?php
// Database configuration and PDO helper
// Replace the placeholder values with your actual DB credentials
$DB_HOST = getenv('SIMS_DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('SIMS_DB_NAME') ?: 'sims';
$DB_USER = getenv('SIMS_DB_USER') ?: 'root';
$DB_PASS = getenv('SIMS_DB_PASS') ?: 'groupCircuit';
$DB_PORT = getenv('SIMS_DB_PORT') ? (int)getenv('SIMS_DB_PORT') : 3306;

function getPDO()
{
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $DB_PORT;
    
    // Debug: log the credentials being used (password masked)
    error_log('[getPDO] Attempting to connect with: host=' . $DB_HOST . ', port=' . $DB_PORT . ', user=' . $DB_USER . ', pass=' . (strlen($DB_PASS) > 0 ? '***' : 'EMPTY'));
    
    // Helpful checks for local development
    if (!extension_loaded('pdo')) {
        error_log('PDO extension is not loaded');
        throw new RuntimeException('PHP PDO extension not available. Please enable PDO in php.ini.');
    }
    if (!extension_loaded('pdo_mysql')) {
        error_log('PDO MySQL driver (pdo_mysql) is not enabled');
        throw new RuntimeException('PHP pdo_mysql extension not available. Please enable pdo_mysql in php.ini.');
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
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            // Log host-specific failure for diagnostics (without sensitive info)
            error_log(sprintf('DB connect to %s failed: %s', $host, $e->getMessage()));
            $lastEx = $e;
            // try next host
        }
    }

    // If we reach here, all attempts failed. Log the detailed exception.
    if ($lastEx) {
        error_log('DB connection failed (all hosts): ' . $lastEx->getMessage());
    } else {
        error_log('DB connection failed: unknown reason');
    }

    // Throw a runtime exception so callers can handle the error (login.php catches exceptions).
    throw new RuntimeException('Database connection failed. Check DB credentials and that MySQL is running.');
}

?>
