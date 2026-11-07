<?php
// controllers/TaskController.php
require_once __DIR__ . '/../services/TaskService.php';

class TaskController {
    public static function handleRequest($action, $method) {
        $user_id = $_SESSION['user_id'];

        if ($method === 'GET') {
            if ($action === 'list') {
                $project_id = (int)($_GET['project_id'] ?? 0);
                return TaskService::listTasks($project_id);
            }
            if ($action === 'search') {
                $q = trim($_GET['q'] ?? '');
                return TaskService::searchTasks($user_id, $q);
            }
            if ($action === 'get') {
                $task_id = (int)($_GET['id'] ?? 0);
                return TaskService::getTaskForEdit($task_id);
            }
        }

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            if ($action === 'create') {
                return TaskService::createTask($user_id, $input);
            }
            if ($action === 'update_status') {
                return TaskService::updateTaskStatus($user_id, $input);
            }
            if ($action === 'add_comment') {
                return TaskService::addComment($user_id, $input);
            }
            if ($action === 'delete') {
                return TaskService::deleteTask($user_id, $input);
            }
        }

        return ['status' => 400, 'error' => 'Invalid request'];
    }
}
