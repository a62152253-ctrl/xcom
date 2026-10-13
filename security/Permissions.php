<?php
namespace Security;
use Exception;

class Permissions {
    public static function requireRole($roles, $user_role) {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        return in_array($user_role, $roles, true);
    }

    public static function hasProjectAccess($db, $project_id, $user_id, $user_role, $minimum_role = 'Member') {
        $project_id = (int)$project_id;
        if ($project_id <= 0) return false;

        if ($user_role === 'Owner' || $user_role === 'Administrator') {
            return true;
        }

        try {
            $stmt = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
            $stmt->execute([$project_id, $user_id]);
            $member = $stmt->fetch();

            if ($member) {
                $hierarchy = ['Member' => 1, 'Administrator' => 2, 'Owner' => 3];
                $user_weight = $hierarchy[$member['role']] ?? 1;
                $min_weight = $hierarchy[$minimum_role] ?? 1;
                return $user_weight >= $min_weight;
            }

            $stmt_created = $db->prepare("SELECT created_by FROM projects WHERE id = ?");
            $stmt_created->execute([$project_id]);
            $project = $stmt_created->fetch();

            return $project && (int)$project['created_by'] === (int)$user_id;

        } catch (Exception $e) {
            error_log("Project access check failed: " . $e->getMessage());
            return false;
        }
    }
}
