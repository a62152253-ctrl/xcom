<?php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../config/database.php';

class TaskService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUserTasks($user_id) {
        $stmt = $this->db->prepare("
            SELECT t.*, p.name as project_name, p.color as project_color,
                   u.full_name as assigned_name, u.avatar as assigned_avatar
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?)
            AND p.is_archived = 0
            ORDER BY FIELD(t.status, 'To Do', 'In Progress', 'Review', 'Done'), t.priority DESC, t.deadline ASC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProjectTasks($project_id, $user_id) {
        $stmt = $this->db->prepare("
            SELECT t.*, p.name as project_name, p.color as project_color,
                   u.full_name as assigned_name, u.avatar as assigned_avatar
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE t.project_id = ?
            AND (p.created_by = ? OR pm.user_id = ?)
            ORDER BY FIELD(t.status, 'To Do', 'In Progress', 'Review', 'Done'), t.priority DESC, t.deadline ASC
        ");
        $stmt->execute([$project_id, $user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
