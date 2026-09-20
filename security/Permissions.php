<?php
// security/Permissions.php

require_once __DIR__ . '/../config/database.php';

class Permissions {
    public static function hasRole($required_role) {
        $user_role = $_SESSION['user_role'] ?? 'Member';
        $roles = ['Member' => 1, 'Administrator' => 2, 'Owner' => 3];

        $user_level = $roles[$user_role] ?? 0;
        $required_level = $roles[$required_role] ?? 0;

        return $user_level >= $required_level;
    }

    public static function canEditProject($project_id, $user_id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT created_by FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();

        if ($project && $project['created_by'] == $user_id) {
            return true;
        }

        if (self::hasRole('Administrator')) {
            return true;
        }

        return false;
    }

    public static function requireRole($required_role) {
        if (!self::hasRole($required_role)) {
            http_response_code(403);
            die("Brak uprawnień.");
        }
    }
}
