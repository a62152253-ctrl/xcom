<?php
// auth/logout.php - Secure logout
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

start_secure_session();

if (is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    log_activity($user_id, 'logout', 'User logged out');
}

// Clear session data
$_SESSION = [];
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

header("Location: /auth/login.php?logout=1");
exit;
