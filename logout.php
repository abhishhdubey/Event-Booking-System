<?php
require_once 'includes/session_guard.php';

// 1. Clear all session variables
$_SESSION = [];

// 2. Delete the session cookie from the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session on the server
session_destroy();

// 4. Send no-cache headers so browser does NOT cache this response
set_no_cache_headers();

// 5. Redirect to login page (clean state)
header('Location: login.php');
exit;
?>
