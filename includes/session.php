<?php
// includes/session.php - ENHANCED SECURITY
require_once __DIR__ . '/../config/config.php';

function start_secure_session() {
    // --- 1. Start session with secure settings ---
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');

        if (!empty($_SERVER['HTTPS']) || getenv('FORCE_HTTPS')) {
            ini_set('session.cookie_secure', 1);
        }

        session_start();
    }

    // --- 2. Check session timeout BEFORE touching CSRF ---
    $now = time();

    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        // Session expired - destroy completely
        session_unset();
        session_destroy();
        
        // Start fresh session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Reset timestamps
        $_SESSION['created_at'] = $now;
        $_SESSION['last_activity'] = $now;

        // Generate fresh CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return true;
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = $now;

    // --- 3. Regenerate session ID every 15 minutes ---
    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = $now;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else if ($now - $_SESSION['created_at'] > 900) {
        session_regenerate_id(true);
        $_SESSION['created_at'] = $now;

        // Regenerate CSRF token after session ID change
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // --- 4. Verify session fingerprint (optional extra layer) ---
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $ip_addr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $fingerprint = hash('sha256', $user_agent . $ip_addr);

    if (isset($_SESSION['fingerprint']) && $_SESSION['fingerprint'] !== $fingerprint) {
        // Potential session hijacking - destroy session
        session_unset();
        session_destroy();
        session_start();
        return false;
    }

    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = $fingerprint;
    }

    // --- 5. Generate CSRF token if missing ---
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return true;
}

function is_logged_in() {
    return isset($_SESSION['user_id']) 
        && !empty($_SESSION['user_id']) 
        && isset($_SESSION['user_status']) 
        && $_SESSION['user_status'] === 'Active';
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: /auth/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}
