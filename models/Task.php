<?php
// models/Task.php

class Task {
    public static function getTasksByProject($db, $project_id) {
        $stmt = $db->prepare("
            SELECT t.*, u.full_name as assigned_name, u.avatar as assigned_avatar
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.project_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$project_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function searchTasks($db, $user_id, $q) {
        $q_safe = '%' . $db->quote($q) . '%';
        $stmt = $db->prepare("
            SELECT t.id, t.name, t.status, p.name as project_name
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?)
              AND (t.name LIKE ? OR t.description LIKE ?)
            LIMIT 10
        ");
        // Remove quotes from $q_safe since quote() adds them and we manually added '%'
        // Alternative: use standard bound parameters
        $search = '%' . $q . '%';
        $stmt->execute([$user_id, $user_id, $search, $search]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getTaskById($db, $task_id) {
        $stmt_t = $db->prepare("SELECT t.*, p.name as project_name, p.color as project_color FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ?");
        $stmt_t->execute([$task_id]);
        return $stmt_t->fetch(PDO::FETCH_ASSOC);
    }

    public static function getTaskDetails($db, $task_id) {
        $stmt_check = $db->prepare("SELECT project_id, name, description, deadline, priority, status, assigned_to FROM tasks WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$task) return null;

        $stmt_comments = $db->prepare("SELECT c.*, u.full_name as user_name FROM task_comments c INNER JOIN users u ON c.user_id = u.id WHERE c.task_id = ? ORDER BY c.created_at ASC");
        $stmt_comments->execute([$task_id]);
        $comments = $stmt_comments->fetchAll(PDO::FETCH_ASSOC);

        $stmt_sub = $db->prepare("SELECT * FROM subtasks WHERE task_id = ?");
        $stmt_sub->execute([$task_id]);
        $subtasks = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);

        $stmt_files = $db->prepare("SELECT id, filename, file_size, uploaded_at FROM task_files WHERE task_id = ?");
        $stmt_files->execute([$task_id]);
        $files = $stmt_files->fetchAll(PDO::FETCH_ASSOC);

        return [
            'task' => $task,
            'comments' => $comments,
            'subtasks' => $subtasks,
            'files' => $files
        ];
    }

    public static function create($db, $project_id, $name, $description, $deadline, $priority, $assigned_to, $created_by) {
        $stmt = $db->prepare("INSERT INTO tasks (project_id, name, description, deadline, priority, status, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, 'To Do', ?, ?)");
        $stmt->execute([$project_id, $name, $description, $deadline ?: null, $priority, $assigned_to, $created_by]);
        return $db->lastInsertId();
    }

    public static function updateStatus($db, $task_id, $status) {
        $db->prepare("UPDATE tasks SET status = ? WHERE id = ?")->execute([$status, $task_id]);
    }

    public static function delete($db, $task_id) {
        $db->prepare("DELETE FROM task_comments WHERE task_id = ?")->execute([$task_id]);
        $db->prepare("DELETE FROM subtasks WHERE task_id = ?")->execute([$task_id]);
        $db->prepare("DELETE FROM task_files WHERE task_id = ?")->execute([$task_id]);
        $db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$task_id]);
    }

    public static function addComment($db, $task_id, $user_id, $comment) {
        $stmt = $db->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$task_id, $user_id, $comment]);
    }
}
