<?php
// api/tasks.php - Fixed API with proper validation & error handling
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

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
            if (!$project_id || !has_project_access($project_id)) {
                http_response_code(403);
                echo json_encode(['error' => 'Brak dostępu do projektu.']);
                exit;
            }
            
            $stmt = $db->prepare("
                SELECT t.*, u.full_name as assigned_name, u.avatar as assigned_avatar
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.project_id = ?
                ORDER BY t.created_at DESC
            ");
            $stmt->execute([$project_id]);
            $tasks = $stmt->fetchAll();
            echo json_encode(['tasks' => $tasks]);
            exit;
        }
        
        // 2. Search tasks globally
        if ($action === 'search') {
            $q = trim($_GET['q'] ?? '');
            if (strlen($q) < 2 || strlen($q) > 255) {
                echo json_encode(['results' => []]);
                exit;
            }
            
            $q_safe = '%' . $db->quote($q) . '%';
            $stmt = $db->prepare("
                SELECT t.id, t.name, t.status, p.name as project_name
                FROM tasks t
                INNER JOIN projects p ON t.project_id = p.id
                LEFT JOIN project_members pm ON p.id = pm.project_id
                WHERE (p.created_by = ? OR pm.user_id = ?) 
                  AND (t.name LIKE ? OR t.description LIKE ?)
                LIMIT 10
            ");
            $stmt->execute([$user_id, $user_id, $q, $q]);
            $results = $stmt->fetchAll();
            echo json_encode(['results' => $results]);
            exit;
        }

        // 3. Get single task for edit modal
        if ($action === 'get') {
            $task_id = (int)($_GET['id'] ?? 0);
            if (!$task_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Brak ID zadania.']);
                exit;
            }
            $stmt_t = $db->prepare("SELECT t.*, p.name as project_name, p.color as project_color FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ?");
            $stmt_t->execute([$task_id]);
            $task = $stmt_t->fetch();
            if (!$task || !has_project_access($task['project_id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Brak dostępu.']);
                exit;
            }
            echo json_encode(['task' => $task]);
            exit;
        }
        
        // 4. Get single task details with subtasks, comments, files
        if ($action === 'detail') {
            $task_id = (int)($_GET['task_id'] ?? 0);
            if (!$task_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Brak ID zadania.']);
                exit;
            }
            
            $stmt_check = $db->prepare("SELECT project_id, name, description, deadline, priority, status, assigned_to FROM tasks WHERE id = ?");
            $stmt_check->execute([$task_id]);
            $task = $stmt_check->fetch();
            
            if (!$task || !has_project_access($task['project_id'])) {
                http_response_code(403);
                echo json_encode(['error' => 'Brak dostępu.']);
                exit;
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
            
            echo json_encode(['task' => $task, 'comments' => $comments, 'subtasks' => $subtasks, 'files' => $files]);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // 1. Create Task
        if ($action === 'create') {
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
            
            // Validate deadline if provided
            if ($deadline && !strtotime($deadline)) {
                http_response_code(400);
                echo json_encode(['error' => 'Niepoprawny format daty.']);
                exit;
            }
            
            $stmt = $db->prepare("INSERT INTO tasks (project_id, name, description, deadline, priority, status, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, 'To Do', ?, ?)");
            $stmt->execute([$project_id, $name, $description, $deadline ?: null, $priority, $assigned_to, $user_id]);
            $task_id = $db->lastInsertId();
            
            if ($assigned_to && $assigned_to != $user_id) {
                create_notification($assigned_to, 'Przypisano nowe zadanie', "Zostałeś przypisany do zadania: $name", 'task_assign');
            }
            
            log_activity($user_id, 'task_create', "Created task '$name' in project $project_id");
            echo json_encode(['success' => true, 'task_id' => $task_id]);
            exit;
        }
        
        // 2. Update Task Status (Kanban DND)
        if ($action === 'update_status') {
            $task_id = (int)($input['task_id'] ?? 0);
            $status = $input['status'] ?? '';
            $allowed_statuses = ['To Do', 'In Progress', 'Review', 'Done'];
            
            if (!$task_id || !in_array($status, $allowed_statuses)) {
                http_response_code(400);
                echo json_encode(['error' => 'Brak wymaganych danych.']);
                exit;
            }
            
            $stmt_check = $db->prepare("SELECT project_id, name, status, assigned_to FROM tasks WHERE id = ?");
            $stmt_check->execute([$task_id]);
            $task = $stmt_check->fetch();
            
            if (!$task || !has_project_access($task['project_id'], 'Member')) {
                http_response_code(403);
                echo json_encode(['error' => 'Brak uprawnień.']);
                exit;
            }
            
            if ($task['status'] !== $status) {
                $db->prepare("UPDATE tasks SET status = ? WHERE id = ?")->execute([$status, $task_id]);
                
                if ($task['assigned_to'] && $task['assigned_to'] != $user_id) {
                    create_notification($task['assigned_to'], 'Status zmieniony', "Zadanie '{$task['name']}' zmieniono na: $status", 'status_change');
                }
                
                log_activity($user_id, 'task_status_update', "Task ID $task_id → $status");
            }
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        // 3. Add comment
        if ($action === 'add_comment') {
            $task_id = (int)($input['task_id'] ?? 0);
            $comment = trim($input['comment'] ?? '');
            
            if (!$task_id || empty($comment) || strlen($comment) > 5000) {
                http_response_code(400);
                echo json_encode(['error' => 'Komentarz jest wymagany i musi być krótsza niż 5000 znaków.']);
                exit;
            }
            
            $stmt_check = $db->prepare("SELECT project_id, name, assigned_to FROM tasks WHERE id = ?");
            $stmt_check->execute([$task_id]);
            $task = $stmt_check->fetch();
            
            if (!$task || !has_project_access($task['project_id'], 'Member')) {
                http_response_code(403);
                echo json_encode(['error' => 'Brak dostępu.']);
                exit;
            }
            
            $stmt = $db->prepare("INSERT INTO task_comments (task_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->execute([$task_id, $user_id, $comment]);
            
            if ($task['assigned_to'] && $task['assigned_to'] != $user_id) {
                create_notification($task['assigned_to'], 'Nowy komentarz', "Dodano komentarz do: {$task['name']}", 'comment');
            }
            
            log_activity($user_id, 'task_comment', "Comment added to task ID $task_id");
            echo json_encode(['success' => true]);
            exit;
        }
        
        // 4. Delete Task
        if ($action === 'delete') {
            $task_id = (int)($input['task_id'] ?? 0);
            if (!$task_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Brak ID zadania.']);
                exit;
            }
            
            $stmt_check = $db->prepare("SELECT project_id, name FROM tasks WHERE id = ?");
            $stmt_check->execute([$task_id]);
            $task = $stmt_check->fetch();
            
            if (!$task || !has_project_access($task['project_id'], 'Administrator')) {
                http_response_code(403);
                echo json_encode(['error' => 'Brak uprawnień do usunięcia.']);
                exit;
            }
            
            $db->prepare("DELETE FROM task_comments WHERE task_id = ?")->execute([$task_id]);
            $db->prepare("DELETE FROM subtasks WHERE task_id = ?")->execute([$task_id]);
            $db->prepare("DELETE FROM task_files WHERE task_id = ?")->execute([$task_id]);
            $db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$task_id]);
            
            log_activity($user_id, 'task_delete', "Deleted: {$task['name']}");
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
