<?php
class Permissions {
    public static function hasProjectAccess($user_id, $project_id, $db = null) {
        if (!$db) {
            $db = Database::getInstance()->getConnection();
        }
        $stmt = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);
        return (bool)$stmt->fetchColumn();
    }
}
