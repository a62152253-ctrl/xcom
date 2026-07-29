<?php
namespace Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Task.php';

use Models\Task;

class TaskService {
    public static function getUserTasks($user_id, $project_id = 0, $task_id = 0) {
        $db = \Database::getInstance()->getConnection();

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

        if ($project_id > 0) {
            $base_query .= " AND t.project_id = ?";
            $params[] = $project_id;
        }

        if ($task_id > 0) {
            $base_query .= " AND t.id = ?";
            $params[] = $task_id;
        }

        $base_query .= " ORDER BY FIELD(t.priority,'Critical','High','Medium','Low'), t.deadline ASC, t.created_at DESC";

        $stmt = $db->prepare($base_query);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        $tasks = [];
        foreach ($results as $row) {
            $tasks[] = new Task($row);
        }

        return $tasks;
    }

    public static function getTaskDetails($user_id, $task_id) {
        $tasks = self::getUserTasks($user_id, 0, $task_id);
        if (empty($tasks)) return null;
        return $tasks[0];
    }
}
