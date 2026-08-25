<?php
// security/Permissions.php

class Permissions {
    public static function checkRole($required_role) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user_role = $_SESSION['user_role'] ?? 'Member';

        $roles = [
            'Owner' => 3,
            'Administrator' => 2,
            'Member' => 1
        ];

        $user_level = $roles[$user_role] ?? 0;
        $required_level = $roles[$required_role] ?? 0;

        return $user_level >= $required_level;
    }

    public static function requireRole($required_role) {
        if (!self::checkRole($required_role)) {
            header('HTTP/1.1 403 Forbidden');
            echo "Access Denied.";
            exit;
        }
    }
}
