<?php
// models/Task.php
require_once __DIR__ . '/../config/database.php';

class Task {
    public static function getUserTasks($db, $user_id, $filter_project) {
        $base_query = "
            SELECT t.*, p.name as project_name, p.color as project_color,
                   u.full_name as assigned_name
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
        ";

        $params = [$user_id, $user_id];

        if ($filter_project) {
            $base_query .= " AND t.project_id = ?";
            $params[] = $filter_project;
        }

        $base_query .= " ORDER BY FIELD(t.priority,'Critical','High','Medium','Low'), t.deadline ASC";

        $stmt_tasks = $db->prepare($base_query);
        $stmt_tasks->execute($params);
        return $stmt_tasks->fetchAll();
    }
}
