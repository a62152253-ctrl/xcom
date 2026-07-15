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
    return isset($_SESSION['user_id']) && $_SESSION['user_status'] === 'Active';
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: /auth/login.php");
        exit;
    }
}
