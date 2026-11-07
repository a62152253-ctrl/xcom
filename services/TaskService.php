<?php
// services/TaskService.php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../security/Permissions.php';

class TaskService {
    public static function getUserTasks($user_id, $filter_project = 0) {
        $db = Database::getInstance()->getConnection();
        return Task::getUserTasks($db, $user_id, $filter_project);
    }

    public static function getTaskById($task_id) {
        $db = Database::getInstance()->getConnection();
        return Task::getTaskById($db, $task_id);
    }

    public static function listTasks($project_id) {
        if (!$project_id || !Permissions::hasProjectAccess($project_id)) {
            return ['status' => 403, 'error' => 'Brak dostępu do projektu.'];
        }
        $db = Database::getInstance()->getConnection();
        return ['status' => 200, 'tasks' => Task::getTasksByProject($db, $project_id)];
    }

    public static function searchTasks($user_id, $q) {
        if (strlen($q) < 2 || strlen($q) > 255) {
            return ['status' => 200, 'results' => []];
        }
        $db = Database::getInstance()->getConnection();
        return ['status' => 200, 'results' => Task::searchTasks($db, $user_id, $q)];
    }

    public static function getTaskForEdit($task_id) {
        if (!$task_id) {
            return ['status' => 400, 'error' => 'Brak ID zadania.'];
        }

        $db = Database::getInstance()->getConnection();
        $task = Task::getTaskById($db, $task_id);

        if (!$task || !Permissions::hasProjectAccess($task['project_id'])) {
            return ['status' => 403, 'error' => 'Brak dostępu.'];
        }

        return ['status' => 200, 'task' => $task];
    }

    public static function createTask($user_id, $input) {
        $project_id = (int)($input['project_id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $deadline = trim($input['deadline'] ?? '');
        $priority = $input['priority'] ?? 'Medium';
        $assigned_to = (int)($input['assigned_to'] ?? 0) ?: null;

        if (!$project_id || !Permissions::hasProjectAccess($project_id, 'Member')) {
            return ['status' => 403, 'error' => 'Brak uprawnień do dodawania zadań.'];
        }

        if (empty($name) || strlen($name) > 255) {
            return ['status' => 400, 'error' => 'Nazwa zadania jest wymagana i musi być krótsza niż 255 znaków.'];
        }

        if (!in_array($priority, ['Low', 'Medium', 'High', 'Critical'])) {
            $priority = 'Medium';
        }

        if ($deadline && !strtotime($deadline)) {
            return ['status' => 400, 'error' => 'Niepoprawny format daty.'];
        }

        $db = Database::getInstance()->getConnection();
        $task_id = Task::createTask($db, [
            'project_id' => $project_id,
            'name' => $name,
            'description' => $description,
            'deadline' => $deadline,
            'priority' => $priority,
            'assigned_to' => $assigned_to,
            'created_by' => $user_id
        ]);

        if ($assigned_to && $assigned_to != $user_id) {
            create_notification($assigned_to, 'Przypisano nowe zadanie', "Zostałeś przypisany do zadania: $name", 'task_assign');
        }

        log_activity($user_id, 'task_create', "Created task '$name' in project $project_id");
        return ['status' => 200, 'success' => true, 'task_id' => $task_id];
    }

    public static function updateTaskStatus($user_id, $input) {
        $task_id = (int)($input['task_id'] ?? 0);
        $status = $input['status'] ?? '';
        $allowed_statuses = ['To Do', 'In Progress', 'Review', 'Done'];

        if (!$task_id || !in_array($status, $allowed_statuses)) {
            return ['status' => 400, 'error' => 'Brak wymaganych danych.'];
        }

        $db = Database::getInstance()->getConnection();
        $task = Task::getTaskById($db, $task_id);

        if (!$task || !Permissions::hasProjectAccess($task['project_id'], 'Member')) {
            return ['status' => 403, 'error' => 'Brak uprawnień.'];
        }

        if ($task['status'] !== $status) {
            Task::updateTaskStatus($db, $task_id, $status);

            if ($task['assigned_to'] && $task['assigned_to'] != $user_id) {
                create_notification($task['assigned_to'], 'Status zmieniony', "Zadanie '{$task['name']}' zmieniono na: $status", 'status_change');
            }

            log_activity($user_id, 'task_status_update', "Task ID $task_id → $status");
        }

        return ['status' => 200, 'success' => true];
    }

    public static function addComment($user_id, $input) {
        $task_id = (int)($input['task_id'] ?? 0);
        $comment = trim($input['comment'] ?? '');

        if (!$task_id || empty($comment) || strlen($comment) > 5000) {
            return ['status' => 400, 'error' => 'Komentarz jest wymagany i musi być krótsza niż 5000 znaków.'];
        }

        $db = Database::getInstance()->getConnection();
        $task = Task::getTaskById($db, $task_id);

        if (!$task || !Permissions::hasProjectAccess($task['project_id'], 'Member')) {
            return ['status' => 403, 'error' => 'Brak dostępu.'];
        }

        Task::addComment($db, $task_id, $user_id, $comment);

        if ($task['assigned_to'] && $task['assigned_to'] != $user_id) {
            create_notification($task['assigned_to'], 'Nowy komentarz', "Dodano komentarz do: {$task['name']}", 'comment');
        }

        log_activity($user_id, 'task_comment', "Comment added to task ID $task_id");
        return ['status' => 200, 'success' => true];
    }

    public static function deleteTask($user_id, $input) {
        $task_id = (int)($input['task_id'] ?? 0);
        if (!$task_id) {
            return ['status' => 400, 'error' => 'Brak ID zadania.'];
        }

        $db = Database::getInstance()->getConnection();
        $task = Task::getTaskById($db, $task_id);

        if (!$task || !Permissions::hasProjectAccess($task['project_id'], 'Administrator')) {
            return ['status' => 403, 'error' => 'Brak uprawnień do usunięcia.'];
        }

        Task::deleteTask($db, $task_id);

        log_activity($user_id, 'task_delete', "Deleted: {$task['name']}");
        return ['status' => 200, 'success' => true];
    }
}
