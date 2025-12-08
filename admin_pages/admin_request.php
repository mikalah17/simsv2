<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('admin');
$userName = htmlspecialchars($_SESSION['name'] ?? 'User');
$userEmail = htmlspecialchars($_SESSION['email'] ?? '');
?>
<?php 
$html = file_get_contents(__DIR__ . '/admin_request.html');
$html = str_replace('FName<br>LName', str_replace(' ', '<br>', $userName), $html);
$html = str_replace('email@gmail.com', $userEmail, $html);
$html = str_replace('href="landing_page.html" class="logout"', 'href="../php/logout.php" class="logout"', $html);
echo $html;
?>
