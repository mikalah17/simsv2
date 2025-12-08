<?php
// login.php
// Expects POST: 'email' and 'password'
// On success: sets session vars and redirects to appropriate dashboard

session_start();
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../sign_in_page.html');
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($email === '' || $password === '') {
    header('Location: ../sign_in_page.html?error=missing');
    exit;
}

try {
    $pdo = getPDO();
    // match actual schema: user_id, first_name, middle_name, last_name, email, password_hash, role_type
    $stmt = $pdo->prepare('SELECT user_id, first_name, middle_name, last_name, email, password_hash, role_type FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
        // Successful login
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'] ?? '';
        $_SESSION['middle_name'] = $user['middle_name'] ?? '';
        $_SESSION['last_name'] = $user['last_name'] ?? '';
        $parts = array_filter([$_SESSION['first_name'], $_SESSION['middle_name'], $_SESSION['last_name']], function($v){ return $v !== ''; });
        $_SESSION['name'] = trim(implode(' ', $parts));
        $_SESSION['role_type'] = $user['role_type'] ?? 'user';
        // legacy key for compatibility
        $_SESSION['role'] = $_SESSION['role_type'];
        $_SESSION['logged_in'] = true;

        // Redirect based on role
        if (strtolower($_SESSION['role_type']) === 'admin') {
            header('Location: ../admin_pages/admin_dashboard.php');
        } else {
            header('Location: ../user_pages/user_dashboard.php');
        }
        exit;
    } else {
        // Invalid credentials
        header('Location: ../sign_in_page.html?error=invalid');
        exit;
    }

} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    header('Location: ../sign_in_page.html?error=server');
    exit;
}

?>
