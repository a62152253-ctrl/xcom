<?php
// auth/logout.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

start_secure_session();

if (is_logged_in()) {
    log_activity($_SESSION['user_id'], 'logout', 'User logged out successfully');
}

// Unset all session values
$_SESSION = [];

// Delete session cookie if active
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

header("Location: /auth/login.php");
exit;
