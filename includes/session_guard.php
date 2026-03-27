<?php
/**
 * session_guard.php
 * Handles secure session configuration, role-based access control, 
 * anti-fixation, and no-cache headers.
 */

// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    // Basic secure session params (if not already set in php.ini)
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

/**
 * Output strict no-cache headers to prevent "Back Button" access
 * after logging out or moving past login screens.
 */
function set_no_cache_headers() {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
}

/**
 * Automatically redirects logged-in users away from login pages 
 * to their respective dashboards.
 */
function prevent_relogin() {
    if (isset($_SESSION['admin_id'])) {
        header('Location: admin-dashboard.php');
        exit;
    }
    if (isset($_SESSION['operator_id'])) {
        header('Location: operator-dashboard.php');
        exit;
    }
    if (isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Protects a route. Only allows users with a specific role.
 * If not logged in, redirects to the login page.
 * If logged in but wrong role, redirects to their own dashboard.
 * 
 * @param array $allowed_roles e.g. ['admin'], ['operator'], ['user']
 */
function require_login($allowed_roles = []) {
    $current_role = null;

    if (isset($_SESSION['admin_id'])) {
        $current_role = 'admin';
    } elseif (isset($_SESSION['operator_id'])) {
        $current_role = 'operator';
    } elseif (isset($_SESSION['user_id'])) {
        $current_role = 'user';
    }

    // If completely unauthenticated, redirect to the general login page
    // (or specific login page based on context, but standard login is safest)
    if (!$current_role) {
        // If we want to be specific, we could pass $login_url
        $login_url = 'login.php';
        if (in_array('admin', $allowed_roles) && count($allowed_roles) === 1) {
            $login_url = 'admin-login.php';
        } elseif (in_array('operator', $allowed_roles) && count($allowed_roles) === 1) {
            $login_url = 'operator-login.php';
        }
        header("Location: $login_url");
        exit;
    }

    // If they have a role but it's not allowed here, redirect them to their home
    if (!in_array($current_role, $allowed_roles)) {
        if ($current_role === 'admin') {
            header('Location: admin-dashboard.php');
            exit;
        } elseif ($current_role === 'operator') {
            header('Location: operator-dashboard.php');
            exit;
        } else {
            header('Location: index.php');
            exit;
        }
    }
}
?>
