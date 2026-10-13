<?php
// services/TaskService.php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../security/Permissions.php';
require_once __DIR__ . '/../includes/functions.php';

class TaskService {
    public static function getUserTasks($user_id, $project_id = null) {
        $db = Database::getInstance()->getConnection();
        return Task::getUserTasks($db, $user_id, $project_id);
    }

    public static function getTaskById($task_id) {
        $db = Database::getInstance()->getConnection();
        return Task::getTaskById($db, $task_id);
    }

    public static function createTask($data) {
        if (!Permissions::hasProjectAccess($data['project_id'], 'Member')) {
            throw new Exception("Brak uprawnień do dodawania zadań.");
        }

        $db = Database::getInstance()->getConnection();
        $task_id = Task::createTask($db, $data);

        if ($data['assigned_to'] && $data['assigned_to'] != $data['created_by']) {
            create_notification($data['assigned_to'], 'Przypisano nowe zadanie', "Zostałeś przypisany do zadania: " . $data['name'], 'task_assign');
        }

        log_activity($data['created_by'], 'task_create', "Created task '{$data['name']}' in project {$data['project_id']}");
        return $task_id;
    }

    public static function updateTask($task_id, $data) {
        $db = Database::getInstance()->getConnection();
        $task = Task::getTaskById($db, $task_id);

        if (!$task || !Permissions::hasProjectAccess($task['project_id'], 'Member')) {
            throw new Exception("Brak uprawnień do edycji tego zadania.");
        }

        Task::updateTask($db, $task_id, $data);

        if ($data['assigned_to'] && $data['assigned_to'] != $task['assigned_to'] && $data['assigned_to'] != $_SESSION['user_id']) {
            create_notification($data['assigned_to'], 'Przypisano zadanie', "Zostałeś przypisany do zadania: " . $data['name'], 'task_assign');
        }

        log_activity($_SESSION['user_id'], 'task_update', "Updated task ID {$task_id}");
        return true;
    }

    public static function updateTaskStatus($task_id, $status) {
        $db = Database::getInstance()->getConnection();
        $task = Task::getTaskById($db, $task_id);

        if (!$task || !Permissions::hasProjectAccess($task['project_id'], 'Member')) {
            throw new Exception("Brak uprawnień do edycji tego zadania.");
        }

        if ($task['status'] !== $status) {
            Task::updateTaskStatus($db, $task_id, $status);

            if ($task['assigned_to'] && $task['assigned_to'] != $_SESSION['user_id']) {
                create_notification($task['assigned_to'], 'Status zmieniony', "Zadanie '{$task['name']}' zmieniono na: $status", 'status_change');
            }
            log_activity($_SESSION['user_id'], 'task_status_update', "Task ID {$task_id} → $status");
        }
        return true;
    }

    public static function deleteTask($task_id) {
        $db = Database::getInstance()->getConnection();
        $task = Task::getTaskById($db, $task_id);

        if (!$task || !Permissions::hasProjectAccess($task['project_id'], 'Administrator')) {
            throw new Exception("Brak uprawnień do usunięcia.");
        }

        Task::deleteTask($db, $task_id);
        log_activity($_SESSION['user_id'], 'task_delete', "Deleted task ID {$task_id}");
        return true;
    }

    public static function searchTasks($user_id, $query) {
        $db = Database::getInstance()->getConnection();
        $q_safe = '%' . $query . '%';
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

    public static function getTaskDetails($task_id) {
        $db = Database::getInstance()->getConnection();
        $stmt_check = $db->prepare("SELECT project_id, name, description, deadline, priority, status, assigned_to FROM tasks WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task = $stmt_check->fetch();

        if (!$task || !Permissions::hasProjectAccess($task['project_id'])) {
            throw new Exception("Brak dostępu.");
        }

        $stmt_comments = $db->prepare("SELECT c.*, u.full_name as user_name FROM task_comments c INNER JOIN users u ON c.user_id = u.id WHERE c.task_id = ? ORDER BY c.created_at ASC");
        $stmt_comments->execute([$task_id]);
        $comments = $stmt_comments->fetchAll();

        $stmt_sub = $db->prepare("SELECT * FROM subtasks WHERE task_id = ?");
        $stmt_sub->execute([$task_id]);
        $subtasks = $stmt_sub->fetchAll();

        $stmt_files = $db->prepare("SELECT id, filename, file_size, uploaded_at FROM task_files WHERE task_id = ?");
        $stmt_files->execute([$task_id]);
        $files = $stmt_files->fetchAll();

        return ['task' => $task, 'comments' => $comments, 'subtasks' => $subtasks, 'files' => $files];
    }

    public static function addComment($task_id, $user_id, $comment) {
        $db = Database::getInstance()->getConnection();
        $stmt_check = $db->prepare("SELECT project_id, name, assigned_to FROM tasks WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task = $stmt_check->fetch();

        if (!$task || !Permissions::hasProjectAccess($task['project_id'], 'Member')) {
            throw new Exception("Brak dostępu.");
        }

        $stmt = $db->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->execute([$task_id, $user_id, $comment]);

        if ($task['assigned_to'] && $task['assigned_to'] != $user_id) {
            create_notification($task['assigned_to'], 'Nowy komentarz', "Dodano komentarz do: {$task['name']}", 'comment');
        }

        log_activity($user_id, 'task_comment', "Comment added to task ID $task_id");
        return true;
    }
}
