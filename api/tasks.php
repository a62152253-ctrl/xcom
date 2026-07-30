<?php
// api/tasks.php - Fixed API with proper validation & error handling
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/TaskService.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$taskService = new TaskService($db, $user_id);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$action = $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action === 'list') {
            $project_id = (int)($_GET['project_id'] ?? 0);
            $result = $taskService->listTasks($project_id);
            if (isset($result['status'])) { http_response_code($result['status']); }
            echo json_encode($result);
            die();
        }
        
        if ($action === 'search') {
            $q = $_GET['q'] ?? '';
            $result = $taskService->searchTasks($q);
            echo json_encode($result);
            die();
        }

        if ($action === 'get') {
            $task_id = (int)($_GET['id'] ?? 0);
            $result = $taskService->getTask($task_id);
            if (isset($result['status'])) { http_response_code($result['status']); }
            echo json_encode($result);
            die();
        }
        
        if ($action === 'detail') {
            $task_id = (int)($_GET['task_id'] ?? 0);
            $result = $taskService->getTaskDetail($task_id);
            if (isset($result['status'])) { http_response_code($result['status']); }
            echo json_encode($result);
            die();
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if ($action === 'create') {
            $result = $taskService->createTask($input);
            if (isset($result['status'])) { http_response_code($result['status']); }
            echo json_encode($result);
            die();
        }
        
        if ($action === 'update_status') {
            $result = $taskService->updateStatus($input);
            if (isset($result['status'])) { http_response_code($result['status']); }
            echo json_encode($result);
            die();
        }
        
        if ($action === 'add_comment') {
            $result = $taskService->addComment($input);
            if (isset($result['status'])) { http_response_code($result['status']); }
            echo json_encode($result);
            die();
        }
        
        if ($action === 'delete') {
            $result = $taskService->deleteTask($input);
            if (isset($result['status'])) { http_response_code($result['status']); }
            echo json_encode($result);
            die();
        }
    }
    
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    die();
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    die();
}
