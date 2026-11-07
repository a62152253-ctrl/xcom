<?php
// models/Task.php

class Task {
    public static function getUserTasks($db, $user_id, $filter_project = 0) {
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

        $stmt = $db->prepare($base_query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getTaskById($db, $task_id) {
        $stmt = $db->prepare("SELECT t.*, p.name as project_name, p.color as project_color FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ? LIMIT 1");
        $stmt->execute([$task_id]);
        return $stmt->fetch();
    }

    public static function getTasksByProject($db, $project_id) {
        $stmt = $db->prepare("
            SELECT t.*, u.full_name as assigned_name, u.avatar as assigned_avatar
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.project_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$project_id]);
        return $stmt->fetchAll();
    }

    public static function searchTasks($db, $user_id, $q) {
        $q_safe = '%' . $q . '%';
        $stmt = $db->prepare("
            SELECT t.id, t.name, t.status, p.name as project_name
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?)
              AND (t.name LIKE ? OR t.description LIKE ?)
            LIMIT 10
        ");
        $stmt->execute([$user_id, $user_id, $q_safe, $q_safe]);
        return $stmt->fetchAll();
    }

    public static function createTask($db, $data) {
        $stmt = $db->prepare("INSERT INTO tasks (project_id, name, description, deadline, priority, status, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, 'To Do', ?, ?)");
        $stmt->execute([
            $data['project_id'],
            $data['name'],
            $data['description'],
            $data['deadline'] ?: null,
            $data['priority'],
            $data['assigned_to'],
            $data['created_by']
        ]);
        return $db->lastInsertId();
    }

    public static function updateTaskStatus($db, $task_id, $status) {
        $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $task_id]);
    }

    public static function deleteTask($db, $task_id) {
        $db->prepare("DELETE FROM task_comments WHERE task_id = ?")->execute([$task_id]);
        $db->prepare("DELETE FROM subtasks WHERE task_id = ?")->execute([$task_id]);
        $db->prepare("DELETE FROM task_files WHERE task_id = ?")->execute([$task_id]);
        $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
        return $stmt->execute([$task_id]);
    }

    public static function addComment($db, $task_id, $user_id, $comment) {
        $stmt = $db->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
        return $stmt->execute([$task_id, $user_id, $comment]);
    }
}
