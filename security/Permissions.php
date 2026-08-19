<?php
class Permissions {
    public static function hasRole($required_role) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user_role = $_SESSION['user_role'] ?? 'Member';

        $roles = [
            'Member' => 1,
            'Administrator' => 2,
            'Owner' => 3
        ];

        $user_level = $roles[$user_role] ?? 0;
        $required_level = $roles[$required_role] ?? 0;

        return $user_level >= $required_level;
    }

    public static function hasProjectAccess($db, $project_id, $user_id, $required_role = 'Member') {
        $stmt = $db->prepare("SELECT created_by FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();

        if (!$project) return false;

        // Owner of the project
        if ($project['created_by'] == $user_id) return true;

        // Check member
        $stmt_mem = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt_mem->execute([$project_id, $user_id]);
        $member = $stmt_mem->fetch();

        if (!$member) return false;

        if ($required_role === 'Administrator' && $member['role'] !== 'Administrator') {
            return false;
        }

        return true;
    }
}
