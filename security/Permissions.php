<?php
// security/Permissions.php
require_once __DIR__ . '/../config/database.php';

class Permissions {
    /**
     * Checks if the currently logged-in user has the required role(s).
     * @param string|array $roles
     * @return bool
     */
    public static function hasRole($roles) {
        if (!is_logged_in()) {
            return false;
        }

        $user_role = $_SESSION['user_role'] ?? 'Member';
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        return in_array($user_role, $roles, true);
    }

    /**
     * Requires the user to have the required role(s), otherwise redirects or exits.
     * @param string|array $roles
     */
    public static function requireRole($roles) {
        if (!is_logged_in()) {
            header("Location: /auth/login.php");
            exit;
        }

        if (!self::hasRole($roles)) {
            http_response_code(403);
            header("Location: /pages/dashboard.php?error=unauthorized");
            exit;
        }
    }

    /**
     * Checks if the user has at least the minimum required role for a specific project.
     * @param int $project_id
     * @param string $minimum_role
     * @return bool
     */
    public static function hasProjectAccess($project_id, $minimum_role = 'Member') {
        if (!is_logged_in()) {
            return false;
        }

        $project_id = (int)$project_id;
        if ($project_id <= 0) {
            return false;
        }

        // Admin and Owner roles bypass project checks globally
        $global_role = $_SESSION['user_role'] ?? 'Member';
        if ($global_role === 'Owner' || $global_role === 'Administrator') {
            return true;
        }

        try {
            $db = Database::getInstance()->getConnection();

            // Check if user is a member of the project
            $stmt = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
            $stmt->execute([$project_id, $_SESSION['user_id']]);
            $member = $stmt->fetch();

            if ($member) {
                $user_project_role = $member['role'];

                // Define role hierarchy
                $hierarchy = ['Member' => 1, 'Administrator' => 2, 'Owner' => 3];

                $user_weight = $hierarchy[$user_project_role] ?? 1;
                $min_weight = $hierarchy[$minimum_role] ?? 1;

                return $user_weight >= $min_weight;
            }

            // Check if user created the project
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
     * Requires the user to have project access, otherwise redirects.
     * @param int $project_id
     * @param string $minimum_role
     */
    public static function requireProjectAccess($project_id, $minimum_role = 'Member') {
        if (!self::hasProjectAccess($project_id, $minimum_role)) {
            http_response_code(403);
            header("Location: /pages/dashboard.php?error=project_access_denied");
            exit;
        }
    }
}
