<?php
class Task {
    public static function getAllByUser(PDO $db, $user_id, $project_id = null) {
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

        if ($project_id) {
            $query .= " AND t.project_id = ?";
            $params[] = $project_id;
        }

        $query .= " ORDER BY FIELD(t.priority,'Critical','High','Medium','Low'), t.deadline ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getById(PDO $db, $task_id) {
        $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        return $stmt->fetch();
    }

    public static function create(PDO $db, $data) {
        $stmt = $db->prepare("
            INSERT INTO tasks (title, description, status, priority, project_id, assigned_to, created_by, due_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['status'] ?? 'TODO',
            $data['priority'] ?? 'Medium',
            $data['project_id'],
            $data['assigned_to'] ?? null,
            $data['created_by'],
            $data['due_date'] ?? null
        ]);
        return $db->lastInsertId();
    }

    public static function updateStatus(PDO $db, $task_id, $status) {
        $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $task_id]);
    }

    public static function delete(PDO $db, $task_id) {
        $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
        return $stmt->execute([$task_id]);
    }
}
