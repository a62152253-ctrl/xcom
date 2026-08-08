<?php
class TaskService {
    public static function getUserTasks(int $user_id, int $filter_project = 0): array {
        $db = Database::getInstance()->getConnection();

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

        if ($filter_project > 0) {
            $base_query .= " AND t.project_id = ?";
            $params[] = $filter_project;
        }

        $base_query .= " ORDER BY FIELD(t.priority,'Critical','High','Medium','Low'), t.deadline ASC";

        $stmt_tasks = $db->prepare($base_query);
        $stmt_tasks->execute($params);
        return $stmt_tasks->fetchAll();
    }

    public static function getTaskById(int $task_id): ?array {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT t.*, p.name as project_name, p.color as project_color FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ? LIMIT 1");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch();
        return $task ?: null;
    }

    public static function createTask(array $data): int {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO tasks (project_id, name, description, deadline, priority, status, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, 'To Do', ?, ?)");
        $stmt->execute([
            $data['project_id'],
            $data['name'],
            $data['description'] ?? '',
            $data['deadline'] ?: null,
            $data['priority'] ?? 'Medium',
            $data['assigned_to'] ?: null,
            $data['created_by']
        ]);
        return (int)$db->lastInsertId();
    }
}
