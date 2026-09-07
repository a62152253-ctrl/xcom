<?php
class Project {
    public static function getActiveProjects($db, $user_id) {
        $stmt = $db->prepare("
            SELECT DISTINCT p.id, p.name, p.color FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
            ORDER BY p.name ASC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }

    public static function getAllProjects($db, $user_id) {
        $stmt = $db->prepare("
            SELECT DISTINCT p.*, u.full_name as creator_name
            FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.created_by = ? OR pm.user_id = ?
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }
}
