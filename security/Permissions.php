<?php
// security/Permissions.php
require_once __DIR__ . '/../config/database.php';

class Permissions {
    public static function hasProjectAccess($user_id, $project_id, $minimum_role = 'Member') {
        $project_id = (int)$project_id;
        if ($project_id <= 0) {
            return false;
        }

        $global_role = $_SESSION['user_role'] ?? 'Member';
        if ($global_role === 'Owner' || $global_role === 'Administrator') {
            return true;
        }

        try {
            $db = Database::getInstance()->getConnection();

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

            $stmt_created = $db->prepare("SELECT created_by FROM projects WHERE id = ?");
            $stmt_created->execute([$project_id]);
            $project = $stmt_created->fetch();

            if ($project && (int)$project['created_by'] === (int)$user_id) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            error_log("Project access check failed: " . $e->getMessage());
            return false;
        }
    }
}
