<?php
// controllers/TaskController.php
require_once __DIR__ . '/../services/TaskService.php';
require_once __DIR__ . '/../includes/session.php';

class TaskController {
    public static function handleRequest($action, $method, $input, $get) {
        try {
            if ($method === 'GET') {
                if ($action === 'list') {
                    $project_id = (int)($get['project_id'] ?? 0);
                    if (!$project_id || !Permissions::hasProjectAccess($project_id)) {
                        http_response_code(403);
                        return ['error' => 'Brak dostępu do projektu.'];
                    }
                    $tasks = TaskService::getUserTasks($_SESSION['user_id'], $project_id);
                    return ['tasks' => $tasks];
                }

                if ($action === 'get') {
                    $task_id = (int)($get['id'] ?? 0);
                    if (!$task_id) {
                        http_response_code(400);
                        return ['error' => 'Brak ID zadania.'];
                    }
                    $task = TaskService::getTaskById($task_id);
                    if (!$task || !Permissions::hasProjectAccess($task['project_id'])) {
                        http_response_code(403);
                        return ['error' => 'Brak dostępu.'];
                    }
                    return ['task' => $task];
                }

                if ($action === 'search') {
                    $q = trim($get['q'] ?? '');
                    if (strlen($q) < 2 || strlen($q) > 255) {
                        return ['results' => []];
                    }
                    $results = TaskService::searchTasks($_SESSION['user_id'], $q);
                    return ['results' => $results];
                }

                if ($action === 'detail') {
                    $task_id = (int)($get['task_id'] ?? 0);
                    if (!$task_id) {
                        http_response_code(400);
                        return ['error' => 'Brak ID zadania.'];
                    }
                    return TaskService::getTaskDetails($task_id);
                }
            }

            if ($method === 'POST') {
                if ($action === 'create') {
                    $data = [
                        'project_id' => (int)($input['project_id'] ?? 0),
                        'name' => trim($input['name'] ?? ''),
                        'description' => trim($input['description'] ?? ''),
                        'deadline' => trim($input['deadline'] ?? ''),
                        'priority' => in_array($input['priority'] ?? '', ['Low', 'Medium', 'High', 'Critical']) ? $input['priority'] : 'Medium',
                        'assigned_to' => (int)($input['assigned_to'] ?? 0) ?: null,
                        'created_by' => $_SESSION['user_id']
                    ];

                    if (empty($data['name']) || strlen($data['name']) > 255) {
                        http_response_code(400);
                        return ['error' => 'Nazwa zadania jest wymagana i musi być krótsza niż 255 znaków.'];
                    }

                    if ($data['deadline'] && !strtotime($data['deadline'])) {
                        http_response_code(400);
                        return ['error' => 'Niepoprawny format daty.'];
                    }

                    $task_id = TaskService::createTask($data);
                    return ['success' => true, 'task_id' => $task_id];
                }

                if ($action === 'update') {
                    $task_id = (int)($input['id'] ?? 0);
                    $data = [
                        'name' => trim($input['name'] ?? ''),
                        'description' => trim($input['description'] ?? ''),
                        'deadline' => trim($input['deadline'] ?? ''),
                        'priority' => in_array($input['priority'] ?? '', ['Low', 'Medium', 'High', 'Critical']) ? $input['priority'] : 'Medium',
                        'status' => in_array($input['status'] ?? '', ['To Do', 'In Progress', 'Review', 'Done']) ? $input['status'] : 'To Do',
                        'assigned_to' => (int)($input['assigned_to'] ?? 0) ?: null
                    ];

                    if (!$task_id || empty($data['name'])) {
                        http_response_code(400);
                        return ['error' => 'Brak wymaganych danych.'];
                    }

                    TaskService::updateTask($task_id, $data);
                    return ['success' => true];
                }

                if ($action === 'update_status') {
                    $task_id = (int)($input['task_id'] ?? 0);
                    $status = $input['status'] ?? '';
                    if (!$task_id || !in_array($status, ['To Do', 'In Progress', 'Review', 'Done'])) {
                        http_response_code(400);
                        return ['error' => 'Brak wymaganych danych.'];
                    }
                    TaskService::updateTaskStatus($task_id, $status);
                    return ['success' => true];
                }

                if ($action === 'add_comment') {
                    $task_id = (int)($input['task_id'] ?? 0);
                    $comment = trim($input['comment'] ?? '');
                    if (!$task_id || empty($comment) || strlen($comment) > 5000) {
                        http_response_code(400);
                        return ['error' => 'Komentarz jest wymagany i musi być krótsza niż 5000 znaków.'];
                    }
                    TaskService::addComment($task_id, $_SESSION['user_id'], $comment);
                    return ['success' => true];
                }

                if ($action === 'delete') {
                    $task_id = (int)($input['id'] ?? $input['task_id'] ?? 0);
                    if (!$task_id) {
                        http_response_code(400);
                        return ['error' => 'Brak ID zadania.'];
                    }
                    TaskService::deleteTask($task_id);
                    return ['success' => true];
                }
            }

            http_response_code(400);
            return ['error' => 'Invalid request'];
        } catch (Exception $e) {
            http_response_code(400);
            return ['error' => $e->getMessage()];
        }
    }
}
