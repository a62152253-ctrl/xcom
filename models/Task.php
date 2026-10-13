<?php
class Task {
    public static function getProjectTasks($db, $project_id) {
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

    public static function create($db, $project_id, $name, $description, $deadline, $priority, $assigned_to, $created_by) {
        $stmt = $db->prepare("INSERT INTO tasks (project_id, name, description, deadline, priority, status, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, 'To Do', ?, ?)");
        $stmt->execute([$project_id, $name, $description, $deadline, $priority, $assigned_to, $created_by]);
        return $db->lastInsertId();
    }
}
