<?php
// auth_check.php - helper functions to protect pages
session_start();

function is_logged_in()
{
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}

/**
 * Require login and optionally require a role.
 * If not logged in (or does not have role), redirects to sign-in.
 */
function require_login($requiredRole = null)
{
    if (!is_logged_in()) {
        header('Location: ../sign_in_page.html?error=auth');
        exit;
    }

    if ($requiredRole !== null) {
        // Prefer `role_type` (schema) but support legacy `role`
        $role = '';
        if (isset($_SESSION['role_type'])) {
            $role = strtolower($_SESSION['role_type']);
        } elseif (isset($_SESSION['role'])) {
            $role = strtolower($_SESSION['role']);
        }
        if (strtolower($requiredRole) !== $role) {
            // Optionally you can redirect to a "not authorized" page
            header('Location: ../sign_in_page.html?error=forbidden');
            exit;
        }
    }
}

?>
