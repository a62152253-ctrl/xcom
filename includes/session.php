<?php
// includes/session.php
require_once __DIR__ . '/../config/config.php';

function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // Force session cookies to be HTTP Only and Secure (if HTTPS is on)
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_start();
    }
    
    // Check if session has timed out
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    
    // Periodic session ID regeneration (every 15 minutes)
    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = time();
    } else if (time() - $_SESSION['created_at'] > 900) {
        session_regenerate_id(true);
        $_SESSION['created_at'] = time();
    }
    
    return true;
}

function is_logged_in() {
    if (isset($_SESSION['user_id']) && $_SESSION['user_status'] === 'Active') {
        return true;
    }

    // Check remember me cookie
    if (isset($_COOKIE['remember_me']) && strpos($_COOKIE['remember_me'], ':') !== false) {
        list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);

        require_once __DIR__ . '/../config/database.php';
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare("SELECT ut.*, u.*, s.theme, s.language FROM user_tokens ut INNER JOIN users u ON ut.user_id = u.id LEFT JOIN settings s ON u.id = s.user_id WHERE ut.selector = ? AND ut.expires_at >= NOW()");
        $stmt->execute([$selector]);
        $token = $stmt->fetch();

        if ($token && hash_equals($token['validator_hash'], hash('sha256', $validator))) {
            if ($token['status'] === 'Active') {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $token['id'];
                $_SESSION['user_email'] = $token['email'];
                $_SESSION['user_name'] = $token['full_name'];
                $_SESSION['user_role'] = $token['role'];
                $_SESSION['user_status'] = $token['status'];
                $_SESSION['user_avatar'] = $token['avatar'];
                $_SESSION['user_theme'] = $token['theme'] ?? 'light';
                $_SESSION['user_language'] = $token['language'] ?? 'pl';

                return true;
            }
        }
    }

    return false;
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: /auth/login.php");
        exit;
    }
}
