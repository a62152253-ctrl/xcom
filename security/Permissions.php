<?php
namespace Security;

use Database;
use Exception;

class Permissions {
    public static function hasProjectAccess(int $user_id, int $project_id, string $minimum_role = 'Member'): bool {
        if ($project_id <= 0 || $user_id <= 0) {
            return false;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Get user's global role
            $stmt_global = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt_global->execute([$user_id]);
            $global_role = $stmt_global->fetchColumn();

            if ($global_role === 'Owner' || $global_role === 'Administrator') {
                return true;
            }

            // Check if user is a member of the project
            $stmt = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
            $stmt->execute([$project_id, $user_id]);
            $member = $stmt->fetch();

            if ($member) {
                $user_project_role = $member['role'];
                $hierarchy = ['Member' => 1, 'Administrator' => 2, 'Owner' => 3];

                $user_weight = $hierarchy[$user_project_role] ?? 1;
                $min_weight = $hierarchy[$minimum_role] ?? 1;

                return $user_weight >= $min_weight;
            }

            // Check if user created the project
            $stmt_created = $db->prepare("SELECT created_by FROM projects WHERE id = ?");
            $stmt_created->execute([$project_id]);
            $project = $stmt_created->fetch();

            if ($project && (int)$project['created_by'] === $user_id) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            error_log("Project access check failed: " . $e->getMessage());
            return false;
        }
    }

    public static function requireRole(int $user_id, $roles): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_role = $stmt->fetchColumn();

            if (!$user_role) {
                return false;
            }

            if (!is_array($roles)) {
                $roles = [$roles];
            }

            return in_array($user_role, $roles, true);
        } catch (Exception $e) {
            error_log("Require role check failed: " . $e->getMessage());
            return false;
        }
    }
}
