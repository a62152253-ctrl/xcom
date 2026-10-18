<?php
// api/tasks.php - Fixed API with proper validation & error handling
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/TaskService.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$action = $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 1. Fetch tasks
        if ($action === 'list') {
            $project_id = (int)($_GET['project_id'] ?? 0);
            $tasks = TaskService::listTasks($db, $project_id);
            echo json_encode(['tasks' => $tasks]);
            exit;
        }
        
        // 2. Search tasks globally
        if ($action === 'search') {
            $q = trim($_GET['q'] ?? '');
            $results = TaskService::searchTasks($db, $user_id, $q);
            echo json_encode(['results' => $results]);
            exit;
        }

        // 3. Get single task for edit modal
        if ($action === 'get') {
            $task_id = (int)($_GET['id'] ?? 0);
            $task = TaskService::getTask($db, $task_id);
            echo json_encode(['task' => $task]);
            exit;
        }
        
        // 4. Get single task details with subtasks, comments, files
        if ($action === 'detail') {
            $task_id = (int)($_GET['task_id'] ?? 0);
            $details = TaskService::getTaskDetails($db, $task_id);
            echo json_encode($details);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // 1. Create Task
        if ($action === 'create') {
            $task_id = TaskService::createTask($db, $user_id, $input);
            echo json_encode(['success' => true, 'task_id' => $task_id]);
            exit;
        }
        
        // 2. Update Task Status (Kanban DND)
        if ($action === 'update_status') {
            TaskService::updateTaskStatus($db, $user_id, $input);
            echo json_encode(['success' => true]);
            exit;
        }
        
        // 3. Add comment
        if ($action === 'add_comment') {
            TaskService::addTaskComment($db, $user_id, $input);
            echo json_encode(['success' => true]);
            exit;
        }
        
        // 4. Delete Task
        if ($action === 'delete') {
            TaskService::deleteTask($db, $user_id, $input);
            echo json_encode(['success' => true]);
            exit;
        }
    }
    
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}
