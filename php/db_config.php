<?php
// Database configuration and PDO helper
// Replace the placeholder values with your actual DB credentials
$DB_HOST = '127.0.0.1';
$DB_NAME = 'your_database';
$DB_USER = 'your_db_user';
$DB_PASS = 'your_db_password';

function getPDO()
{
    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        // Don't expose DB errors to the user in production
        http_response_code(500);
        die('Database connection failed.');
    }
}

?>
