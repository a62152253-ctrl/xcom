<?php
// controllers/TaskController.php
require_once __DIR__ . '/../services/TaskService.php';

class TaskController {
    private $service;

    public function __construct($db, $user_id) {
        $this->service = new TaskService($db, $user_id);
    }

    public function handleRequest($action, $method) {
        $response = null;

        try {
            if ($method === 'GET') {
                switch ($action) {
                    case 'list':
                        $project_id = (int)($_GET['project_id'] ?? 0);
                        $response = $this->service->listTasks($project_id);
                        break;
                    case 'search':
                        $q = trim($_GET['q'] ?? '');
                        $response = $this->service->searchTasks($q);
                        break;
                    case 'get':
                        $task_id = (int)($_GET['id'] ?? 0);
                        $response = $this->service->getTask($task_id);
                        break;
                    case 'detail':
                        $task_id = (int)($_GET['task_id'] ?? 0);
                        $response = $this->service->getTaskDetails($task_id);
                        break;
                    default:
                        $response = ['error' => 'Nieznana akcja', 'status' => 400];
                }
            } elseif ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true) ?? [];
                switch ($action) {
                    case 'create':
                        $response = $this->service->createTask($input);
                        break;
                    case 'update_status':
                        $task_id = (int)($input['task_id'] ?? 0);
                        $status = $input['status'] ?? '';
                        $response = $this->service->updateTaskStatus($task_id, $status);
                        break;
                    case 'add_comment':
                        $task_id = (int)($input['task_id'] ?? 0);
                        $comment = trim($input['comment'] ?? '');
                        $response = $this->service->addComment($task_id, $comment);
                        break;
                    case 'delete':
                        $task_id = (int)($input['task_id'] ?? 0);
                        $response = $this->service->deleteTask($task_id);
                        break;
                    default:
                        $response = ['error' => 'Nieznana akcja', 'status' => 400];
                }
            } else {
                $response = ['error' => 'Niedozwolona metoda HTTP', 'status' => 405];
            }
        } catch (Exception $e) {
            error_log("TaskController Error: " . $e->getMessage());
            $response = ['error' => 'Błąd serwera', 'status' => 500];
        }

        $status = $response['status'] ?? 200;
        unset($response['status']);

        http_response_code($status);
        echo json_encode($response);
        exit;
    }
}
