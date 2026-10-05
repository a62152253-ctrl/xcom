<?php
// controllers/TaskController.php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../includes/functions.php';

class TaskController {
    public static function handleGet($db, $user_id, $action) {
        if ($action === 'list') {
            $project_id = (int)($_GET['project_id'] ?? 0);
            if (!$project_id || !has_project_access($project_id)) {
                self::err(403, 'Brak dostępu do projektu.');
            }
            self::ok(['tasks' => Task::getAllByProject($db, $project_id)]);
        }
        if ($action === 'search') {
            $q = trim($_GET['q'] ?? '');
            if (strlen($q) < 2 || strlen($q) > 255) { self::ok(['results' => []]); }
            self::ok(['results' => Task::search($db, $q, $user_id)]);
        }
        if ($action === 'get') {
            $task_id = (int)($_GET['id'] ?? 0);
            if (!$task_id) self::err(400, 'Brak ID zadania.');
            $task = Task::getById($db, $task_id);
            if (!$task || !has_project_access($task['project_id'])) self::err(403, 'Brak dostępu.');
            self::ok(['task' => $task]);
        }
        if ($action === 'detail') {
            $task_id = (int)($_GET['task_id'] ?? 0);
            if (!$task_id) self::err(400, 'Brak ID zadania.');
            $task = Task::getSimpleById($db, $task_id);
            if (!$task || !has_project_access($task['project_id'])) self::err(403, 'Brak dostępu.');
            self::ok([
                'task' => $task,
                'comments' => Task::getComments($db, $task_id),
                'subtasks' => Task::getSubtasks($db, $task_id),
                'files' => Task::getFiles($db, $task_id)
            ]);
        }
    }

    public static function handlePost($db, $user_id, $action, $input) {
        if ($action === 'create') self::createTask($db, $user_id, $input);
        if ($action === 'update_status') self::updateTaskStatus($db, $user_id, $input);
        if ($action === 'add_comment') self::addTaskComment($db, $user_id, $input);
        if ($action === 'delete') self::deleteTask($db, $user_id, $input);
    }

    private static function err($code, $msg) {
        http_response_code($code);
        echo json_encode(['error' => $msg]);
        exit;
    }

    private static function ok($data) {
        echo json_encode($data);
        exit;
    }

    private static function createTask($db, $user_id, $input) {
        $project_id = (int)($input['project_id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $deadline = trim($input['deadline'] ?? '');
        $priority = $input['priority'] ?? 'Medium';
        $assigned_to = (int)($input['assigned_to'] ?? 0) ?: null;

        if (!$project_id || !has_project_access($project_id, 'Member')) self::err(403, 'Brak uprawnień do dodawania zadań.');
        if (empty($name) || strlen($name) > 255) self::err(400, 'Nazwa zadania jest wymagana i musi być krótsza niż 255 znaków.');
        if (!in_array($priority, ['Low', 'Medium', 'High', 'Critical'])) $priority = 'Medium';
        if ($deadline && !strtotime($deadline)) self::err(400, 'Niepoprawny format daty.');

        $task_id = Task::create($db, $project_id, $name, $description, $deadline, $priority, $assigned_to, $user_id);

        if ($assigned_to && $assigned_to != $user_id) {
            create_notification($assigned_to, 'Przypisano nowe zadanie', "Zostałeś przypisany do zadania: $name", 'task_assign');
        }
        log_activity($user_id, 'task_create', "Created task '$name' in project $project_id");
        self::ok(['success' => true, 'task_id' => $task_id]);
    }

    private static function updateTaskStatus($db, $user_id, $input) {
        $task_id = (int)($input['task_id'] ?? 0);
        $status = $input['status'] ?? '';

        if (!$task_id || !in_array($status, ['To Do', 'In Progress', 'Review', 'Done'])) self::err(400, 'Brak wymaganych danych.');

        $task = Task::getSimpleById($db, $task_id);
        if (!$task || !has_project_access($task['project_id'], 'Member')) self::err(403, 'Brak uprawnień.');

        if ($task['status'] !== $status) {
            Task::updateStatus($db, $task_id, $status);
            if ($task['assigned_to'] && $task['assigned_to'] != $user_id) {
                create_notification($task['assigned_to'], 'Status zmieniony', "Zadanie '{$task['name']}' zmieniono na: $status", 'status_change');
            }
            log_activity($user_id, 'task_status_update', "Task ID $task_id → $status");
        }
        self::ok(['success' => true]);
    }

    private static function addTaskComment($db, $user_id, $input) {
        $task_id = (int)($input['task_id'] ?? 0);
        $comment = trim($input['comment'] ?? '');

        if (!$task_id || empty($comment) || strlen($comment) > 5000) self::err(400, 'Komentarz jest wymagany.');

        $task = Task::getSimpleById($db, $task_id);
        if (!$task || !has_project_access($task['project_id'], 'Member')) self::err(403, 'Brak dostępu.');

        Task::addComment($db, $task_id, $user_id, $comment);
        if ($task['assigned_to'] && $task['assigned_to'] != $user_id) {
            create_notification($task['assigned_to'], 'Nowy komentarz', "Dodano komentarz do: {$task['name']}", 'comment');
        }
        log_activity($user_id, 'task_comment', "Comment added to task ID $task_id");
        self::ok(['success' => true]);
    }

    private static function deleteTask($db, $user_id, $input) {
        $task_id = (int)($input['task_id'] ?? 0);
        if (!$task_id) self::err(400, 'Brak ID zadania.');

        $task = Task::getSimpleById($db, $task_id);
        if (!$task || !has_project_access($task['project_id'], 'Administrator')) self::err(403, 'Brak uprawnień do usunięcia.');

        Task::delete($db, $task_id);
        log_activity($user_id, 'task_delete', "Deleted: {$task['name']}");
        self::ok(['success' => true]);
    }
}
