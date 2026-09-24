<?php
// models/Task.php
require_once __DIR__ . '/../config/database.php';

class Task {
    public static function getUserTasks($user_id, $project_id = 0) {
        $db = Database::getInstance()->getConnection();

        $query = "
            SELECT t.*, p.name as project_name, p.color as project_color,
                   u.full_name as assigned_name
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
        ";

        $params = [$user_id, $user_id];

        if ($project_id > 0) {
            $query .= " AND t.project_id = ?";
            $params[] = $project_id;
        }

        $query .= " ORDER BY FIELD(t.priority,'Critical','High','Medium','Low'), t.deadline ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getTaskById($task_id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT t.*, p.name as project_name, p.color as project_color FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ? LIMIT 1");
        $stmt->execute([$task_id]);
        return $stmt->fetch();
    }
}
