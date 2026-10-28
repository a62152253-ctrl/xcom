<?php
// security/Csrf.php

class Csrf {
    public static function validateToken($token = null) {
        if ($token === null) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $token = $input['csrf_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }

        return $token && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    public static function requireToken() {
        if (!self::validateToken()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'CSRF token validation failed']);
            die();
        }
    }
}
