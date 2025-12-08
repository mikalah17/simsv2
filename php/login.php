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
    $stmt = $pdo->prepare('SELECT id, email, password_hash, first_name, last_name, role FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
        // Successful login
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $_SESSION['role'] = $user['role'] ?? 'user';
        $_SESSION['logged_in'] = true;

        // Redirect based on role (customize names as needed)
        if (strtolower($_SESSION['role']) === 'admin') {
            header('Location: ../admin_pages/admin_dashboard.html');
        } else {
            header('Location: ../user_pages/user_dashboard.html');
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
