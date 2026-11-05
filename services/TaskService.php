<?php
// services/TaskService.php
require_once __DIR__ . '/../models/Task.php';

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
        return ['tasks' => Task::getList($this->db, $project_id), 'status' => 200];
    }

    public function searchTasks($q) {
        if (strlen($q) < 2 || strlen($q) > 255) {
            return ['results' => [], 'status' => 200];
        }
        return ['results' => Task::search($this->db, $this->user_id, $q), 'status' => 200];
    }

    public function getTask($task_id) {
        if (!$task_id) {
            return ['error' => 'Brak ID zadania.', 'status' => 400];
        }
        $task = Task::getById($this->db, $task_id);
        if (!$task || !has_project_access($task['project_id'])) {
            return ['error' => 'Brak dostępu.', 'status' => 403];
        }
        return ['task' => $task, 'status' => 200];
    }

    public function getTaskDetails($task_id) {
        if (!$task_id) {
            return ['error' => 'Brak ID zadania.', 'status' => 400];
        }
        $task = Task::getBasicDetails($this->db, $task_id);
        if (!$task || !has_project_access($task['project_id'])) {
            return ['error' => 'Brak dostępu.', 'status' => 403];
        }

        $comments = Task::getComments($this->db, $task_id);
        $subtasks = Task::getSubtasks($this->db, $task_id);
        $files = Task::getFiles($this->db, $task_id);

        return ['task' => $task, 'comments' => $comments, 'subtasks' => $subtasks, 'files' => $files, 'status' => 200];
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

        $task_id = Task::create($this->db, $project_id, $name, $description, $deadline, $priority, $assigned_to, $this->user_id);

        if ($assigned_to && $assigned_to != $this->user_id) {
            create_notification($assigned_to, 'Przypisano nowe zadanie', "Zostałeś przypisany do zadania: $name", 'task_assign');
        }
        log_activity($this->user_id, 'task_create', "Created task '$name' in project $project_id");

        return ['success' => true, 'task_id' => $task_id, 'status' => 200];
    }

    public function updateTaskStatus($task_id, $status) {
        $allowed_statuses = ['To Do', 'In Progress', 'Review', 'Done'];
        if (!$task_id || !in_array($status, $allowed_statuses)) {
            return ['error' => 'Brak wymaganych danych.', 'status' => 400];
        }

        $task = Task::getBasicDetails($this->db, $task_id);
        if (!$task || !has_project_access($task['project_id'], 'Member')) {
            return ['error' => 'Brak uprawnień.', 'status' => 403];
        }

        if ($task['status'] !== $status) {
            Task::updateStatus($this->db, $task_id, $status);
            if ($task['assigned_to'] && $task['assigned_to'] != $this->user_id) {
                create_notification($task['assigned_to'], 'Status zmieniony', "Zadanie '{$task['name']}' zmieniono na: $status", 'status_change');
            }
            log_activity($this->user_id, 'task_status_update', "Task ID $task_id → $status");
        }

        return ['success' => true, 'status' => 200];
    }

    public function addComment($task_id, $comment) {
        if (!$task_id || empty($comment) || strlen($comment) > 5000) {
            return ['error' => 'Komentarz jest wymagany i musi być krótsza niż 5000 znaków.', 'status' => 400];
        }

        $task = Task::getBasicDetails($this->db, $task_id);
        if (!$task || !has_project_access($task['project_id'], 'Member')) {
            return ['error' => 'Brak dostępu.', 'status' => 403];
        }

        Task::addComment($this->db, $task_id, $this->user_id, $comment);

        if ($task['assigned_to'] && $task['assigned_to'] != $this->user_id) {
            create_notification($task['assigned_to'], 'Nowy komentarz', "Dodano komentarz do: {$task['name']}", 'comment');
        }
        log_activity($this->user_id, 'task_comment', "Comment added to task ID $task_id");

        return ['success' => true, 'status' => 200];
    }

    public function deleteTask($task_id) {
        if (!$task_id) {
            return ['error' => 'Brak ID zadania.', 'status' => 400];
        }

        $task = Task::getBasicDetails($this->db, $task_id);
        if (!$task || !has_project_access($task['project_id'], 'Administrator')) {
            return ['error' => 'Brak uprawnień do usunięcia.', 'status' => 403];
        }

        Task::delete($this->db, $task_id);
        log_activity($this->user_id, 'task_delete', "Deleted: {$task['name']}");

        return ['success' => true, 'status' => 200];
    }
}
