<?php
// security/Permissions.php
class Permissions {
    public static function requireRole($roles) {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /auth/login.php");
            die();
        }

        $user_role = $_SESSION['user_role'] ?? 'Member';
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        if (!in_array($user_role, $roles, true)) {
            http_response_code(403);
            header("Location: /pages/dashboard.php?error=unauthorized");
            die();
        }
    }
}
