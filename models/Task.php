<?php
// models/Task.php

class Task {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listByProject($project_id, $user_id) {
        $stmt = $this->db->prepare("
            SELECT t.*, p.name as project_name, p.color as project_color, u.full_name as assigned_name, u.avatar as assigned_avatar,
                   (SELECT COUNT(*) FROM task_comments WHERE task_id = t.id) as comment_count,
                   (SELECT COUNT(*) FROM subtasks WHERE task_id = t.id) as subtask_total,
                   (SELECT COUNT(*) FROM subtasks WHERE task_id = t.id AND is_completed = 1) as subtask_done
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.project_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$project_id]);
        return $stmt->fetchAll();
    }

    public function search($q, $user_id) {
        $q = "%$q%";
        $stmt = $this->db->prepare("
            SELECT t.id, t.name, t.status, t.priority, p.name as project_name
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?)
              AND (t.name LIKE ? OR t.description LIKE ?)
            LIMIT 10
        ");
        $stmt->execute([$user_id, $user_id, $q, $q]);
        return $stmt->fetchAll();
    }

    public function getById($task_id) {
        $stmt = $this->db->prepare("SELECT t.*, p.name as project_name, p.color as project_color FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ?");
        $stmt->execute([$task_id]);
        return $stmt->fetch();
    }

    public function getBasicInfo($task_id) {
        $stmt = $this->db->prepare("SELECT project_id, name, description, deadline, priority, status, assigned_to FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        return $stmt->fetch();
    }

    public function getComments($task_id) {
        $stmt = $this->db->prepare("SELECT c.*, u.full_name as user_name FROM task_comments c INNER JOIN users u ON c.user_id = u.id WHERE c.task_id = ? ORDER BY c.created_at ASC");
        $stmt->execute([$task_id]);
        return $stmt->fetchAll();
    }

    public function getSubtasks($task_id) {
        $stmt = $this->db->prepare("SELECT * FROM subtasks WHERE task_id = ?");
        $stmt->execute([$task_id]);
        return $stmt->fetchAll();
    }

    public function getFiles($task_id) {
        $stmt = $this->db->prepare("SELECT id, filename, file_size, uploaded_at FROM task_files WHERE task_id = ?");
        $stmt->execute([$task_id]);
        return $stmt->fetchAll();
    }

    public function create($project_id, $name, $description, $deadline, $priority, $assigned_to, $user_id) {
        $stmt = $this->db->prepare("INSERT INTO tasks (project_id, name, description, deadline, priority, status, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, 'To Do', ?, ?)");
        $stmt->execute([$project_id, $name, $description, $deadline ?: null, $priority, $assigned_to, $user_id]);
        return $this->db->lastInsertId();
    }

    public function updateStatus($task_id, $status) {
        return $this->db->prepare("UPDATE tasks SET status = ? WHERE id = ?")->execute([$status, $task_id]);
    }

    public function addComment($task_id, $user_id, $comment) {
        return $this->db->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)")->execute([$task_id, $user_id, $comment]);
    }

    public function delete($task_id) {
        $this->db->prepare("DELETE FROM task_comments WHERE task_id = ?")->execute([$task_id]);
        $this->db->prepare("DELETE FROM subtasks WHERE task_id = ?")->execute([$task_id]);
        $this->db->prepare("DELETE FROM task_files WHERE task_id = ?")->execute([$task_id]);
        return $this->db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$task_id]);
    }
}
