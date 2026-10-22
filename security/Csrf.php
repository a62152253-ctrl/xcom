<?php
// security/Csrf.php

class Csrf {
    /**
     * Generates and returns a secure CSRF token.
     * Updates the session if no valid token is found or generation is forced.
     */
    public static function generateToken($force = false) {
        if ($force || empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validates a provided token against the one stored in the session.
     * Uses hash_equals for timing-attack safe comparison.
     */
    public static function validateToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
