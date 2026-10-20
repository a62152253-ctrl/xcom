<?php
namespace Security;

class Permissions {
    public static function hasRole($required_roles) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user_role = $_SESSION['user_role'] ?? 'Member';
        if (!is_array($required_roles)) {
            $required_roles = [$required_roles];
        }
        return in_array($user_role, $required_roles, true);
    }
}
