<?php
// services/TaskService.php
require_once __DIR__ . '/../includes/functions.php';

class TaskService {
    private $db;
    private $user_id;

    public function __construct($db, $user_id) {
        $this->db = $db;
        $this->user_id = $user_id;
    }

    public function listTasks($project_id) {
        if (!$project_id || !has_project_access($project_id)) {
            return ['error' => 'Brak dostępu do projektu.', 'status' => 403];
        }

        $stmt = $this->db->prepare("
            SELECT t.*, u.full_name as assigned_name, u.avatar as assigned_avatar
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.project_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$project_id]);
        return ['tasks' => $stmt->fetchAll()];
    }

    public function searchTasks($query) {
        $q = trim($query);
        if (strlen($q) < 2 || strlen($q) > 255) {
            return ['results' => []];
        }

        $stmt = $this->db->prepare("
            SELECT t.id, t.name, t.status, p.name as project_name
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?)
              AND (t.name LIKE ? OR t.description LIKE ?)
            LIMIT 10
        ");
        $stmt->execute([$this->user_id, $this->user_id, "%$q%", "%$q%"]);
        return ['results' => $stmt->fetchAll()];
    }

    public function getTask($task_id) {
        if (!$task_id) {
            return ['error' => 'Brak ID zadania.', 'status' => 400];
        }
        $stmt = $this->db->prepare("SELECT t.*, p.name as project_name, p.color as project_color FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ?");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch();
        if (!$task || !has_project_access($task['project_id'])) {
            return ['error' => 'Brak dostępu.', 'status' => 403];
        }
        return ['task' => $task];
    }

    public function getTaskDetail($task_id) {
        if (!$task_id) {
            return ['error' => 'Brak ID zadania.', 'status' => 400];
        }

        $stmt_check = $this->db->prepare("SELECT project_id, name, description, deadline, priority, status, assigned_to FROM tasks WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task = $stmt_check->fetch();

        if (!$task || !has_project_access($task['project_id'])) {
            return ['error' => 'Brak dostępu.', 'status' => 403];
        }

        $stmt_comments = $this->db->prepare("SELECT c.*, u.full_name as user_name FROM task_comments c INNER JOIN users u ON c.user_id = u.id WHERE c.task_id = ? ORDER BY c.created_at ASC");
        $stmt_comments->execute([$task_id]);

        $stmt_sub = $this->db->prepare("SELECT * FROM subtasks WHERE task_id = ?");
        $stmt_sub->execute([$task_id]);

        $stmt_files = $this->db->prepare("SELECT id, filename, file_size, uploaded_at FROM task_files WHERE task_id = ?");
        $stmt_files->execute([$task_id]);

        return [
            'task' => $task,
            'comments' => $stmt_comments->fetchAll(),
            'subtasks' => $stmt_sub->fetchAll(),
            'files' => $stmt_files->fetchAll()
        ];
    }

    public function createTask($data) {
        $project_id = (int)($data['project_id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $deadline = trim($data['deadline'] ?? '');
        $priority = $data['priority'] ?? 'Medium';
        $assigned_to = (int)($data['assigned_to'] ?? 0) ?: null;

        if (!$project_id || !has_project_access($project_id, 'Member')) {
            return ['error' => 'Brak uprawnień do dodawania zadań.', 'status' => 403];
        }
        if (empty($name) || strlen($name) > 255) {
            return ['error' => 'Nazwa zadania jest wymagana i musi być krótsza niż 255 znaków.', 'status' => 400];
        }
        if (!in_array($priority, ['Low', 'Medium', 'High', 'Critical'])) {
            $priority = 'Medium';
        }
        if ($deadline && !strtotime($deadline)) {
            return ['error' => 'Niepoprawny format daty.', 'status' => 400];
        }

        $stmt = $this->db->prepare("INSERT INTO tasks (project_id, name, description, deadline, priority, status, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, 'To Do', ?, ?)");
        $stmt->execute([$project_id, $name, $description, $deadline ?: null, $priority, $assigned_to, $this->user_id]);
        $task_id = $this->db->lastInsertId();

        if ($assigned_to && $assigned_to != $this->user_id) {
            create_notification($assigned_to, 'Przypisano nowe zadanie', "Zostałeś przypisany do zadania: $name", 'task_assign');
        }
        log_activity($this->user_id, 'task_create', "Created task '$name' in project $project_id");
        return ['success' => true, 'task_id' => $task_id];
    }

    public function updateStatus($data) {
        $task_id = (int)($data['task_id'] ?? 0);
        $status = $data['status'] ?? '';
        $allowed_statuses = ['To Do', 'In Progress', 'Review', 'Done'];

        if (!$task_id || !in_array($status, $allowed_statuses)) {
            return ['error' => 'Brak wymaganych danych.', 'status' => 400];
        }

        $stmt_check = $this->db->prepare("SELECT project_id, name, status, assigned_to FROM tasks WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task = $stmt_check->fetch();

        if (!$task || !has_project_access($task['project_id'], 'Member')) {
            return ['error' => 'Brak uprawnień.', 'status' => 403];
        }

        if ($task['status'] !== $status) {
            $this->db->prepare("UPDATE tasks SET status = ? WHERE id = ?")->execute([$status, $task_id]);
            if ($task['assigned_to'] && $task['assigned_to'] != $this->user_id) {
                create_notification($task['assigned_to'], 'Status zmieniony', "Zadanie '{$task['name']}' zmieniono na: $status", 'status_change');
            }
            log_activity($this->user_id, 'task_status_update', "Task ID $task_id → $status");
        }
        return ['success' => true];
    }

    public function addComment($data) {
        $task_id = (int)($data['task_id'] ?? 0);
        $comment = trim($data['comment'] ?? '');

        if (!$task_id || empty($comment) || strlen($comment) > 5000) {
            return ['error' => 'Komentarz jest wymagany i musi być krótsza niż 5000 znaków.', 'status' => 400];
        }

        $stmt_check = $this->db->prepare("SELECT project_id, name, assigned_to FROM tasks WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task = $stmt_check->fetch();

        if (!$task || !has_project_access($task['project_id'], 'Member')) {
            return ['error' => 'Brak dostępu.', 'status' => 403];
        }

        $stmt = $this->db->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$task_id, $this->user_id, $comment]);

        if ($task['assigned_to'] && $task['assigned_to'] != $this->user_id) {
            create_notification($task['assigned_to'], 'Nowy komentarz', "Dodano komentarz do: {$task['name']}", 'comment');
        }
        log_activity($this->user_id, 'task_comment', "Comment added to task ID $task_id");
        return ['success' => true];
    }

    public function deleteTask($data) {
        $task_id = (int)($data['task_id'] ?? 0);
        if (!$task_id) {
            return ['error' => 'Brak ID zadania.', 'status' => 400];
        }

        $stmt_check = $this->db->prepare("SELECT project_id, name FROM tasks WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task = $stmt_check->fetch();

        if (!$task || !has_project_access($task['project_id'], 'Administrator')) {
            return ['error' => 'Brak uprawnień do usunięcia.', 'status' => 403];
        }

        $this->db->prepare("DELETE FROM task_comments WHERE task_id = ?")->execute([$task_id]);
        $this->db->prepare("DELETE FROM subtasks WHERE task_id = ?")->execute([$task_id]);
        $this->db->prepare("DELETE FROM task_files WHERE task_id = ?")->execute([$task_id]);
        $this->db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$task_id]);

        log_activity($this->user_id, 'task_delete', "Deleted: {$task['name']}");
        return ['success' => true];
    }
}
