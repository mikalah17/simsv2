<?php
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['role_type'] = 'user';
$_SESSION['first_name'] = 'Test';
$_SESSION['last_name'] = 'User';
$_SESSION['email'] = 'testuser@sims.local';
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'user';

// Test sidebar rendering
ob_start();
include __DIR__ . '/user_pages/user_sidebar.php';
$sidebar_html = ob_get_clean();

echo "=== SIDEBAR HTML ===\n";
echo htmlspecialchars($sidebar_html);
echo "\n\n=== LENGTH: " . strlen($sidebar_html) . " bytes ===\n";

// Check if sidebar div exists
if (strpos($sidebar_html, '<div class="sidebar"') !== false) {
    echo "✓ Sidebar div found\n";
} else {
    echo "✗ Sidebar div NOT found\n";
}

if (strpos($sidebar_html, 'nav-links') !== false) {
    echo "✓ nav-links div found\n";
} else {
    echo "✗ nav-links div NOT found\n";
}
?>
