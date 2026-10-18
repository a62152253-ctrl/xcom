<?php
// security/Csrf.php
require_once __DIR__ . '/../includes/session.php';

class Csrf {
    public static function require_csrf_token() {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $token = $input['csrf_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            throw new Exception('CSRF token validation failed', 403);
        }
    }

    public static function validate_csrf($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
    }
}
