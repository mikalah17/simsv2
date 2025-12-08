<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('admin');
include __DIR__ . '/admin_dashboard.html';
?>
