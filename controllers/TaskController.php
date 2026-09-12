<?php
// controllers/TaskController.php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../includes/functions.php';

class TaskController {
    private $db;
    private $taskModel;
    private $userId;

    public function __construct($db, $userId) {
        $this->db = $db;
        $this->taskModel = new Task($db);
        $this->userId = $userId;
    }

    public function handleRequest($method, $action) {
        if ($method === 'GET') {
            switch ($action) {
                case 'list':
                    $this->listTasks();
                    break;
                case 'search':
                    $this->searchTasks();
                    break;
                case 'get':
                    $this->getTask();
                    break;
                case 'detail':
                    $this->getTaskDetail();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Błędne zapytanie']);
                    exit;
            }
        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            switch ($action) {
                case 'create':
                    $this->createTask($input);
                    break;
                case 'update_status':
                    $this->updateStatus($input);
                    break;
                case 'add_comment':
                    $this->addComment($input);
                    break;
                case 'delete':
                    $this->deleteTask($input);
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Błędne zapytanie']);
                    exit;
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }
    }

    private function listTasks() {
        $project_id = (int)($_GET['project_id'] ?? 0);
        if (!$project_id || !has_project_access($project_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak dostępu do projektu.']);
            exit;
        }
        $tasks = $this->taskModel->listByProject($project_id, $this->userId);
        echo json_encode(['tasks' => $tasks]);
        exit;
    }

    private function searchTasks() {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['results' => []]);
            exit;
        }
        $results = $this->taskModel->search($q, $this->userId);
        echo json_encode(['results' => $results]);
        exit;
    }

    private function getTask() {
        $task_id = (int)($_GET['id'] ?? 0);
        if (!$task_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID zadania.']);
            exit;
        }
        $task = $this->taskModel->getById($task_id);
        if (!$task || !has_project_access($task['project_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak dostępu.']);
            exit;
        }
        echo json_encode(['task' => $task]);
        exit;
    }

    private function getTaskDetail() {
        $task_id = (int)($_GET['task_id'] ?? 0);
        if (!$task_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID zadania.']);
            exit;
        }
        $task = $this->taskModel->getBasicInfo($task_id);
        if (!$task || !has_project_access($task['project_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak dostępu.']);
            exit;
        }
        $comments = $this->taskModel->getComments($task_id);
        $subtasks = $this->taskModel->getSubtasks($task_id);
        $files = $this->taskModel->getFiles($task_id);
        echo json_encode(['task' => $task, 'comments' => $comments, 'subtasks' => $subtasks, 'files' => $files]);
        exit;
    }

    private function createTask($input) {
        $project_id = (int)($input['project_id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $deadline = trim($input['deadline'] ?? '');
        $priority = $input['priority'] ?? 'Medium';
        $assigned_to = (int)($input['assigned_to'] ?? 0) ?: null;

        if (!$project_id || !has_project_access($project_id, 'Member')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień do dodawania zadań.']);
            exit;
        }

        if (empty($name) || strlen($name) > 255) {
            http_response_code(400);
            echo json_encode(['error' => 'Nazwa zadania jest wymagana i musi być krótsza niż 255 znaków.']);
            exit;
        }

        if (!in_array($priority, ['Low', 'Medium', 'High', 'Critical'])) {
            $priority = 'Medium';
        }

        if ($deadline && !strtotime($deadline)) {
            http_response_code(400);
            echo json_encode(['error' => 'Niepoprawny format daty.']);
            exit;
        }

        $task_id = $this->taskModel->create($project_id, $name, $description, $deadline, $priority, $assigned_to, $this->userId);

        if ($assigned_to && $assigned_to != $this->userId) {
            create_notification($assigned_to, 'Przypisano nowe zadanie', "Zostałeś przypisany do zadania: $name", 'task_assign');
        }

        log_activity($this->userId, 'task_create', "Created task '$name' in project $project_id");
        echo json_encode(['success' => true, 'task_id' => $task_id]);
        exit;
    }

    private function updateStatus($input) {
        $task_id = (int)($input['task_id'] ?? 0);
        $status = $input['status'] ?? '';
        $allowed_statuses = ['To Do', 'In Progress', 'Review', 'Done'];

        if (!$task_id || !in_array($status, $allowed_statuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak wymaganych danych.']);
            exit;
        }

        $task = $this->taskModel->getBasicInfo($task_id);

        if (!$task || !has_project_access($task['project_id'], 'Member')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }

        if ($task['status'] !== $status) {
            $this->taskModel->updateStatus($task_id, $status);

            if ($task['assigned_to'] && $task['assigned_to'] != $this->userId) {
                create_notification($task['assigned_to'], 'Status zmieniony', "Zadanie '{$task['name']}' zmieniono na: $status", 'status_change');
            }

            log_activity($this->userId, 'task_status_update', "Task ID $task_id → $status");
        }

        echo json_encode(['success' => true]);
        exit;
    }

    private function addComment($input) {
        $task_id = (int)($input['task_id'] ?? 0);
        $comment = trim($input['comment'] ?? '');

        if (!$task_id || empty($comment) || strlen($comment) > 5000) {
            http_response_code(400);
            echo json_encode(['error' => 'Komentarz jest wymagany i musi być krótsza niż 5000 znaków.']);
            exit;
        }

        $task = $this->taskModel->getBasicInfo($task_id);

        if (!$task || !has_project_access($task['project_id'], 'Member')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak dostępu.']);
            exit;
        }

        $this->taskModel->addComment($task_id, $this->userId, $comment);

        if ($task['assigned_to'] && $task['assigned_to'] != $this->userId) {
            create_notification($task['assigned_to'], 'Nowy komentarz', "Dodano komentarz do: {$task['name']}", 'comment');
        }

        log_activity($this->userId, 'task_comment', "Comment added to task ID $task_id");
        echo json_encode(['success' => true]);
        exit;
    }

    private function deleteTask($input) {
        $task_id = (int)($input['task_id'] ?? 0);
        if (!$task_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID zadania.']);
            exit;
        }

        $task = $this->taskModel->getBasicInfo($task_id);

        if (!$task || !has_project_access($task['project_id'], 'Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień do usunięcia.']);
            exit;
        }

        $this->taskModel->delete($task_id);

        log_activity($this->userId, 'task_delete', "Deleted: {$task['name']}");
        echo json_encode(['success' => true]);
        exit;
    }
}
