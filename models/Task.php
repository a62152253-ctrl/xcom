<?php
// models/Task.php
require_once __DIR__ . '/../config/database.php';

class Task {
    public static function getUserTasks($db, $user_id, $project_id = null) {
        $base_query = "
            SELECT t.*, p.name as project_name, p.color as project_color,
                   u.full_name as assigned_name, u.avatar as assigned_avatar
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
        ";

        $params = [$user_id, $user_id];

        if ($project_id) {
            $base_query .= " AND t.project_id = ?";
            $params[] = $project_id;
        }

        $base_query .= " ORDER BY FIELD(t.priority,'Critical','High','Medium','Low'), t.deadline ASC";

        $stmt = $db->prepare($base_query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getTaskById($db, $task_id) {
        $stmt = $db->prepare("SELECT t.*, p.name as project_name, p.color as project_color FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ?");
        $stmt->execute([$task_id]);
        return $stmt->fetch();
    }

    public static function createTask($db, $data) {
        $stmt = $db->prepare("INSERT INTO tasks (project_id, name, description, deadline, priority, status, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, 'To Do', ?, ?)");
        $stmt->execute([
            $data['project_id'],
            $data['name'],
            $data['description'],
            $data['deadline'],
            $data['priority'],
            $data['assigned_to'],
            $data['created_by']
        ]);
        return $db->lastInsertId();
    }

    public static function updateTask($db, $task_id, $data) {
        $stmt = $db->prepare("UPDATE tasks SET name = ?, description = ?, deadline = ?, priority = ?, status = ?, assigned_to = ? WHERE id = ?");
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['deadline'],
            $data['priority'],
            $data['status'],
            $data['assigned_to'],
            $task_id
        ]);
        return true;
    }

    public static function updateTaskStatus($db, $task_id, $status) {
        $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$status, $task_id]);
        return true;
    }

    public static function deleteTask($db, $task_id) {
        $db->prepare("DELETE FROM task_comments WHERE task_id = ?")->execute([$task_id]);
        $db->prepare("DELETE FROM subtasks WHERE task_id = ?")->execute([$task_id]);
        $db->prepare("DELETE FROM task_files WHERE task_id = ?")->execute([$task_id]);
        $db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$task_id]);
        return true;
    }
}
