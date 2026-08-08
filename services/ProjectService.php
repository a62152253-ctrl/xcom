<?php
class ProjectService {
    public static function getUserProjects(int $user_id): array {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT DISTINCT p.id, p.name, p.color
            FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
            ORDER BY p.name ASC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }
}
