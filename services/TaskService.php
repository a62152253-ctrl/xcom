<?php
// services/TaskService.php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../security/Permissions.php';
require_once __DIR__ . '/../security/SecurityAudit.php';
require_once __DIR__ . '/../notifications/NotificationService.php';
require_once __DIR__ . '/../includes/functions.php';

class TaskService {

    public static function listTasks($db, $project_id) {
        if (!$project_id || !Permissions::has_project_access($project_id)) {
            http_response_code(403);
            return ['error' => 'Brak dostępu do projektu.'];
        }
        return Task::getTasksByProject($db, $project_id);
    }

    public static function searchTasks($db, $user_id, $q) {
        if (strlen($q) < 2 || strlen($q) > 255) {
            return [];
        }
        return Task::searchTasks($db, $user_id, $q);
    }

    public static function getTask($db, $task_id) {
        if (!$task_id) {
            http_response_code(400);
            return ['error' => 'Brak ID zadania.'];
        }
        $task = Task::getTaskById($db, $task_id);
        if (!$task || !Permissions::has_project_access($task['project_id'])) {
            http_response_code(403);
            return ['error' => 'Brak dostępu.'];
        }
        return $task;
    }

    public static function getTaskDetails($db, $task_id) {
        if (!$task_id) {
            http_response_code(400);
            return ['error' => 'Brak ID zadania.'];
        }

        $details = Task::getTaskDetails($db, $task_id);

        if (!$details || !Permissions::has_project_access($details['task']['project_id'])) {
            http_response_code(403);
            return ['error' => 'Brak dostępu.'];
        }

        return $details;
    }

    public static function createTask($db, $user_id, $data) {
        $project_id = (int)($data['project_id'] ?? 0);
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $deadline = trim($data['deadline'] ?? '');
        $priority = $data['priority'] ?? 'Medium';
        $assigned_to = (int)($data['assigned_to'] ?? 0) ?: null;

        if (!$project_id || !Permissions::has_project_access($project_id, 'Member')) {
            throw new Exception('Brak uprawnień do dodawania zadań.', 403);
        }

        if (empty($name) || strlen($name) > 255) {
            throw new Exception('Nazwa zadania jest wymagana i musi być krótsza niż 255 znaków.', 400);
        }

        if (!in_array($priority, ['Low', 'Medium', 'High', 'Critical'])) {
            $priority = 'Medium';
        }

        if ($deadline && !strtotime($deadline)) {
            throw new Exception('Niepoprawny format daty.', 400);
        }

        $task_id = Task::create($db, $project_id, $name, $description, $deadline, $priority, $assigned_to, $user_id);

        if ($assigned_to && $assigned_to != $user_id) {
            NotificationService::create($assigned_to, 'Przypisano nowe zadanie', "Zostałeś przypisany do zadania: $name", 'task_assign');
        }

        SecurityAudit::log_activity($user_id, 'task_create', "Created task '$name' in project $project_id");
        return $task_id;
    }

    public static function updateTaskStatus($db, $user_id, $data) {
        $task_id = (int)($data['task_id'] ?? 0);
        $status = $data['status'] ?? '';
        $allowed_statuses = ['To Do', 'In Progress', 'Review', 'Done'];

        if (!$task_id || !in_array($status, $allowed_statuses)) {
            throw new Exception('Brak wymaganych danych.', 400);
        }

        $stmt_check = $db->prepare("SELECT project_id, name, status, assigned_to FROM tasks WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task = $stmt_check->fetch();

        if (!$task || !Permissions::has_project_access($task['project_id'], 'Member')) {
            throw new Exception('Brak uprawnień.', 403);
        }

        if ($task['status'] !== $status) {
            Task::updateStatus($db, $task_id, $status);

            if ($task['assigned_to'] && $task['assigned_to'] != $user_id) {
                NotificationService::create($task['assigned_to'], 'Status zmieniony', "Zadanie '{$task['name']}' zmieniono na: $status", 'status_change');
            }

            SecurityAudit::log_activity($user_id, 'task_status_update', "Task ID $task_id → $status");
        }
    }

    public static function addTaskComment($db, $user_id, $data) {
        $task_id = (int)($data['task_id'] ?? 0);
        $comment = trim($data['comment'] ?? '');

        if (!$task_id || empty($comment) || strlen($comment) > 5000) {
            throw new Exception('Komentarz jest wymagany i musi być krótsza niż 5000 znaków.', 400);
        }

        $stmt_check = $db->prepare("SELECT project_id, name, assigned_to FROM tasks WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task = $stmt_check->fetch();

        if (!$task || !Permissions::has_project_access($task['project_id'], 'Member')) {
            throw new Exception('Brak dostępu.', 403);
        }

        Task::addComment($db, $task_id, $user_id, $comment);

        if ($task['assigned_to'] && $task['assigned_to'] != $user_id) {
            NotificationService::create($task['assigned_to'], 'Nowy komentarz', "Dodano komentarz do: {$task['name']}", 'comment');
        }

        SecurityAudit::log_activity($user_id, 'task_comment', "Comment added to task ID $task_id");
    }

    public static function deleteTask($db, $user_id, $data) {
        $task_id = (int)($data['task_id'] ?? 0);
        if (!$task_id) {
            throw new Exception('Brak ID zadania.', 400);
        }

        $stmt_check = $db->prepare("SELECT project_id, name FROM tasks WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task = $stmt_check->fetch();

        if (!$task || !Permissions::has_project_access($task['project_id'], 'Administrator')) {
            throw new Exception('Brak uprawnień do usunięcia.', 403);
        }

        Task::delete($db, $task_id);

        SecurityAudit::log_activity($user_id, 'task_delete', "Deleted: {$task['name']}");
    }
}
