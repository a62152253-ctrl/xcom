<?php
// security/Csrf.php

class Csrf {
    /**
     * Generates a CSRF token if one does not exist, or forces a new one.
     * @return string
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    /**
     * Validates a given CSRF token against the one stored in the session.
     * @param string|null $token
     * @return bool
     */
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
