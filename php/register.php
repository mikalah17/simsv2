<?php
// register.php
// Handles user registration, matches SIMS schema and auto-logs-in new user

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

    // Determine next user_id if table doesn't AUTO_INCREMENT
    $nextId = null;
    $r = $pdo->query('SELECT MAX(user_id) AS m FROM users');
    $row = $r->fetch();
    if ($row && isset($row['m']) && $row['m'] !== null) {
        $nextId = ((int)$row['m']) + 1;
    } else {
        $nextId = 1;
    }

    // Insert new user matching SIMS schema (user_id, first_name, middle_name, last_name, email, password_hash, role_type)
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $pdo->prepare('INSERT INTO users (user_id, first_name, middle_name, last_name, email, password_hash, role_type) VALUES (:id, :fn, :mn, :ln, :email, :hash, :role)');
    $insert->execute([
        ':id' => $nextId,
        ':fn' => $first_name,
        ':mn' => ($middle_name === '' ? null : $middle_name),
        ':ln' => $last_name,
        ':email' => $email,
        ':hash' => $hash,
        ':role' => 'user'
    ]);

    // === AUDIT RECORDING ===
    // Get next audit_id
    $auditIdStmt = $pdo->query('SELECT MAX(audit_id) AS m FROM audit');
    $auditRow = $auditIdStmt->fetch();
    $nextAuditId = 1;
    if ($auditRow && isset($auditRow['m']) && $auditRow['m'] !== null) {
        $nextAuditId = ((int)$auditRow['m']) + 1;
    }

    // Insert audit record for account creation
    $auditStmt = $pdo->prepare('INSERT INTO audit (audit_id, user_id, actionType, tableAffected, record_id, action_desc, actionTime) VALUES (:aid, :uid, :atype, :table, :rid, :desc, :time)');
    $auditStmt->execute([
        ':aid' => $nextAuditId,
        ':uid' => $nextId,
        ':atype' => 'CREATE',
        ':table' => 'users',
        ':rid' => $nextId,
        ':desc' => 'New user account created',
        ':time' => date('Y-m-d H:i:s')
    ]);
    // === END AUDIT ===

    // Auto-login the new user
    session_start();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $nextId;
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $first_name;
    $_SESSION['middle_name'] = ($middle_name === '' ? '' : $middle_name);
    $_SESSION['last_name'] = $last_name;
    $parts = array_filter([$_SESSION['first_name'], $_SESSION['middle_name'], $_SESSION['last_name']], function($v){ return $v !== ''; });
    $_SESSION['name'] = trim(implode(' ', $parts));
    $_SESSION['role_type'] = 'user';
    $_SESSION['role'] = 'user';
    $_SESSION['logged_in'] = true;

    // Redirect to user dashboard
    header('Location: ../user_pages/user_dashboard.php');
    exit;

} catch (Exception $e) {
    error_log('Register error: ' . $e->getMessage());
    header('Location: ../sign_up_page.html?error=server');
    exit;
}

?>