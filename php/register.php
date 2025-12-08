<?php
// register.php
// Handles user registration: validates input, checks for existing email,
// stores password hash and redirects back to sign-in on success.

require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../sign_up_page.html');
    exit;
}

$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$middle_name = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : '';
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Basic validation
if ($first_name === '' || $last_name === '' || $email === '' || $password === '' || $confirm === '') {
    header('Location: ../sign_up_page.html?error=missing');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../sign_up_page.html?error=invalid_email');
    exit;
}

if ($password !== $confirm) {
    header('Location: ../sign_up_page.html?error=password_mismatch');
    exit;
}

if (strlen($password) < 6) {
    header('Location: ../sign_up_page.html?error=password_short');
    exit;
}

try {
    $pdo = getPDO();

    // Check if email already exists
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        header('Location: ../sign_up_page.html?error=email_taken');
        exit;
    }

    // Insert new user matching SIMS schema (first_name, middle_name, last_name, email, password, role_type)
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $pdo->prepare('INSERT INTO users (first_name, middle_name, last_name, email, password, role_type) VALUES (:fn, :mn, :ln, :email, :hash, :role)');
    $insert->execute([
        ':fn' => $first_name,
        ':mn' => ($middle_name === '' ? null : $middle_name),
':ln' => $last_name,
        ':email' => $email,
        ':hash' => $hash,
        ':role' => 'user'
    ]);

    // Registration successful — redirect to sign-in with success message
    header('Location: ../sign_in_page.html?success=registered');
    exit;

} catch (Exception $e) {
    error_log('Register error: ' . $e->getMessage());
    header('Location: ../sign_up_page.html?error=server');
    exit;
}

?>
