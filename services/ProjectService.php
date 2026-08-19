<?php
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../config/database.php';

class ProjectService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUserProjects($user_id) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.id, p.name, p.color
            FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?)
            AND p.is_archived = 0
            ORDER BY p.name ASC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
