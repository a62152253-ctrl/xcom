<?php
// api/tasks.php
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 1. Fetch tasks
    if ($action === 'list') {
        $project_id = $_GET['project_id'] ?? null;
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
        if (strlen($q) < 2) {
            echo json_encode(['results' => []]);
            exit;
        }
        
        // Search in tasks where user has project access
        $stmt = $db->prepare("
            SELECT t.id, t.name, t.status, p.name as project_name
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?) 
              AND (t.name LIKE ? OR t.description LIKE ?)
            LIMIT 10
        ");
        $like_q = "%$q%";
        $stmt->execute([$user_id, $user_id, $like_q, $like_q]);
        $results = $stmt->fetchAll();
        echo json_encode(['results' => $results]);
        exit;
    }

    // 3b. Get single task for edit modal
    if ($action === 'get') {
        $task_id = (int)($_GET['id'] ?? 0);
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
    
    // 3. Get single task details with subtasks, comments, files
    if ($action === 'detail') {
        $task_id = $_GET['task_id'] ?? null;
        if (!$task_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID zadania.']);
            exit;
        }
        
        // Check access
        $stmt_check = $db->prepare("SELECT project_id, name, description, deadline, priority, status, assigned_to FROM tasks WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task = $stmt_check->fetch();
        
        if (!$task || !has_project_access($task['project_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak dostępu.']);
            exit;
        }
        
        // Fetch comments
        $stmt_comments = $db->prepare("
            SELECT c.*, u.full_name as user_name
            FROM task_comments c
            INNER JOIN users u ON c.user_id = u.id
            WHERE c.task_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt_comments->execute([$task_id]);
        $comments = $stmt_comments->fetchAll();
        
        // Fetch subtasks
        $stmt_sub = $db->prepare("SELECT * FROM subtasks WHERE task_id = ?");
        $stmt_sub->execute([$task_id]);
        $subtasks = $stmt_sub->fetchAll();
        
        // Fetch files
        $stmt_files = $db->prepare("SELECT * FROM task_files WHERE task_id = ?");
        $stmt_files->execute([$task_id]);
        $files = $stmt_files->fetchAll();
        
        echo json_encode([
            'task' => $task,
            'comments' => $comments,
            'subtasks' => $subtasks,
            'files' => $files
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // 1. Create Task
    if ($action === 'create') {
        $project_id = $input['project_id'] ?? null;
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $deadline = trim($input['deadline'] ?? null);
        $priority = $input['priority'] ?? 'Medium';
        $status = $input['status'] ?? 'To Do';
        $assigned_to = $input['assigned_to'] ?? null;
        
        if (!$project_id || !has_project_access($project_id, 'Member')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień do dodawania zadań.']);
            exit;
        }
        
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nazwa zadania jest wymagana.']);
            exit;
        }
        
        $stmt = $db->prepare("INSERT INTO tasks (project_id, name, description, deadline, priority, status, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$project_id, $name, $description, $deadline ?: null, $priority, $status, $assigned_to ?: null, $user_id]);
        $task_id = $db->lastInsertId();
        
        // Send Notification if assigned
        if ($assigned_to && $assigned_to != $user_id) {
            create_notification($assigned_to, 'Przypisano nowe zadanie', "Zostałeś przypisany do zadania '$name'.", 'task_assign');
        }
        
        log_activity($user_id, 'task_create', "Created task '$name' in project $project_id");
        echo json_encode(['success' => true, 'task_id' => $task_id]);
        exit;
    }
    
    // 2. Update Task Status (Kanban DND)
    if ($action === 'update_status') {
        $task_id = $input['task_id'] ?? null;
        $status = $input['status'] ?? null;
        
        if (!$task_id || !$status) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak wymaganych danych.']);
            exit;
        }
        
        // Verify task and project access
        $stmt_check = $db->prepare("SELECT project_id, name, status, assigned_to, created_by FROM tasks WHERE id = ?");
        $stmt_check->execute([$task_id]);
        $task = $stmt_check->fetch();
        
        if (!$task || !has_project_access($task['project_id'], 'Member')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->execute([$status, $task_id]);
        
        // Notify assignee if status changed by someone else
        if ($task['assigned_to'] && $task['assigned_to'] != $user_id) {
            create_notification($task['assigned_to'], 'Status zadania zmieniony', "Status zadania '{$task['name']}' został zmieniony na '$status'.", 'status_change');
        }
        
        log_activity($user_id, 'task_status_update', "Changed status of task ID $task_id to $status");
        echo json_encode(['success' => true]);
        exit;
    }
    
    // 3. Add comment
    if ($action === 'add_comment') {
        $task_id = $input['task_id'] ?? null;
        $comment = trim($input['comment'] ?? '');
        
        if (!$task_id || empty($comment)) {
            http_response_code(400);
            echo json_encode(['error' => 'Komentarz nie może być pusty.']);
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
            create_notification($task['assigned_to'], 'Nowy komentarz', "Użytkownik skomentował zadanie '{$task['name']}'.", 'comment');
        }
        
        log_activity($user_id, 'task_comment', "Added comment to task ID $task_id");
        echo json_encode(['success' => true]);
        exit;
    }
    
    // 4. Toggle subtask completion
    if ($action === 'toggle_subtask') {
        $subtask_id = $input['subtask_id'] ?? null;
        $is_completed = $input['is_completed'] ?? 0;
        
        if (!$subtask_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID podzadania.']);
            exit;
        }
        
        // Access verification
        $stmt_sub = $db->prepare("SELECT t.project_id FROM subtasks s INNER JOIN tasks t ON s.task_id = t.id WHERE s.id = ?");
        $stmt_sub->execute([$subtask_id]);
        $project_id = $stmt_sub->fetchColumn();
        
        if (!$project_id || !has_project_access($project_id, 'Member')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak dostępu.']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE subtasks SET is_completed = ? WHERE id = ?");
        $stmt->execute([$is_completed ? 1 : 0, $subtask_id]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    // 5. Add subtask
    if ($action === 'add_subtask') {
        $task_id = $input['task_id'] ?? null;
        $title = trim($input['title'] ?? '');
        
        if (!$task_id || empty($title)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nazwa podzadania jest wymagana.']);
            exit;
        }
        
        $stmt_task = $db->prepare("SELECT project_id FROM tasks WHERE id = ?");
        $stmt_task->execute([$task_id]);
        $project_id = $stmt_task->fetchColumn();
        
        if (!$project_id || !has_project_access($project_id, 'Member')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak dostępu.']);
            exit;
        }
        
        $stmt = $db->prepare("INSERT INTO subtasks (task_id, title) VALUES (?, ?)");
        $stmt->execute([$task_id, $title]);
        $subtask_id = $db->lastInsertId();
        
        echo json_encode(['success' => true, 'subtask_id' => $subtask_id]);
        exit;
    }

    // update_status — drag & drop kanban
    if ($action === 'update_status') {
        $id = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? '';
        $allowed = ['To Do', 'In Progress', 'Review', 'Done'];
        if (!$id || !in_array($status, $allowed)) {
            http_response_code(400); echo json_encode(['error' => 'Nieprawidłowe dane.']); exit;
        }
        $check = $db->prepare("SELECT project_id, name FROM tasks WHERE id = ?");
        $check->execute([$id]);
        $t = $check->fetch();
        if (!$t || !has_project_access($t['project_id'], 'Member')) {
            http_response_code(403); echo json_encode(['error' => 'Brak uprawnień.']); exit;
        }
        $db->prepare("UPDATE tasks SET status = ? WHERE id = ?")->execute([$status, $id]);
        log_activity($user_id, 'task_status_change', "Changed task '$t[name]' status to $status");
        echo json_encode(['success' => true]);
        exit;
    }

    // update — full edit from modal
    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $project_id = (int)($input['project_id'] ?? 0);
        if (!$id || !$name || !$project_id) {
            http_response_code(400); echo json_encode(['error' => 'Brak wymaganych pól.']); exit;
        }
        if (!has_project_access($project_id, 'Member')) {
            http_response_code(403); echo json_encode(['error' => 'Brak uprawnień.']); exit;
        }
        $priority = in_array($input['priority']??'', ['Low','Medium','High','Critical']) ? $input['priority'] : 'Medium';
        $status   = in_array($input['status']??'',   ['To Do','In Progress','Review','Done']) ? $input['status'] : 'To Do';
        $db->prepare("UPDATE tasks SET name=?,description=?,project_id=?,assigned_to=?,priority=?,status=?,deadline=? WHERE id=?")
           ->execute([$name, $input['description']??'', $project_id, $input['assigned_to']??null, $priority, $status, $input['deadline']??null, $id]);
        log_activity($user_id, 'task_update', "Updated task: $name");
        echo json_encode(['success' => true]);
        exit;
    }

    // delete
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $check = $db->prepare("SELECT project_id, name FROM tasks WHERE id = ?");
        $check->execute([$id]);
        $t = $check->fetch();
        if (!$t || !has_project_access($t['project_id'], 'Administrator')) {
            http_response_code(403); echo json_encode(['error' => 'Brak uprawnień.']); exit;
        }
        $db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$id]);
        log_activity($user_id, 'task_delete', "Deleted task: {$t['name']}");
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid Request']);
exit;
