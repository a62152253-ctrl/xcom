<?php
// security/Permissions.php
class Permissions {
    public static function hasRole($required_roles) {
        $user_role = $_SESSION['user_role'] ?? '';
        if (is_array($required_roles)) {
            return in_array($user_role, $required_roles);
        }
        return $user_role === $required_roles;
    }

    public static function canManageProject($db, $user_id, $project_id) {
        $stmt = $db->prepare("SELECT created_by FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        if ($project && $project['created_by'] == $user_id) {
            return true;
        }

        $stmt = $db->prepare("SELECT user_id FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);
        if ($stmt->fetch()) {
            return true;
        }

        return self::hasRole(['Owner', 'Administrator']);
    }
}
