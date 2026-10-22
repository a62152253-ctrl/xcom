<?php
// security/Permissions.php
require_once __DIR__ . '/../config/database.php';

class Permissions {

    /**
     * Ensures the current user has at least one of the specified roles globally.
     */
    public static function requireRole($roles) {
        if (!is_logged_in()) {
            header("Location: /auth/login.php");
            exit;
        }

        $user_role = $_SESSION['user_role'] ?? 'Member';
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        if (!in_array($user_role, $roles, true)) {
            http_response_code(403);
            header("Location: /pages/dashboard.php?error=unauthorized");
            exit;
        }
    }

    /**
     * Checks if the current user has the minimum required role for a specific project.
     * Admins and Owners bypass project checks globally.
     */
    public static function hasProjectAccess($project_id, $minimum_role = 'Member') {
        if (!is_logged_in()) {
            return false;
        }

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

            // Check project_members
            $stmt = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
            $stmt->execute([$project_id, $_SESSION['user_id']]);
            $member = $stmt->fetch();

            if ($member) {
                $user_project_role = $member['role'];
                $hierarchy = ['Member' => 1, 'Administrator' => 2, 'Owner' => 3];

                $user_weight = $hierarchy[$user_project_role] ?? 1;
                $min_weight = $hierarchy[$minimum_role] ?? 1;

                return $user_weight >= $min_weight;
            }

            // Check project creator
            $stmt_created = $db->prepare("SELECT created_by FROM projects WHERE id = ?");
            $stmt_created->execute([$project_id]);
            $project = $stmt_created->fetch();

            if ($project && (int)$project['created_by'] === (int)$_SESSION['user_id']) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            error_log("Project access check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Redirects to the dashboard if the user lacks the required project access.
     */
    public static function requireProjectAccess($project_id, $minimum_role = 'Member') {
        if (!self::hasProjectAccess($project_id, $minimum_role)) {
            http_response_code(403);
            header("Location: /pages/dashboard.php?error=project_access_denied");
            exit;
        }
    }
}
