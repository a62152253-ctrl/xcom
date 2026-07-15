<?php
// api/projects.php
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all accessible projects
    $stmt = $db->prepare("
        SELECT DISTINCT p.*, u.full_name as creator_name 
        FROM projects p 
        LEFT JOIN project_members pm ON p.id = pm.project_id 
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.created_by = ? OR pm.user_id = ?
    ");
    $stmt->execute([$user_id, $user_id]);
    $projects = $stmt->fetchAll();
    
    echo json_encode(['projects' => $projects]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Create Project
    if ($action === 'create') {
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $color = trim($input['color'] ?? '#3b82f6');
        $deadline = trim($input['deadline'] ?? null);
        
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nazwa projektu jest wymagana.']);
            exit;
        }
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("INSERT INTO projects (name, description, color, deadline, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $color, $deadline ?: null, $user_id]);
            $project_id = $db->lastInsertId();
            
            // Add creator as Project Owner
            $stmt_member = $db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'Owner')");
            $stmt_member->execute([$project_id, $user_id]);
            
            $db->commit();
            
            log_activity($user_id, 'project_create', 'Created project: ' . sanitize($name));
            echo json_encode(['success' => true, 'project_id' => $project_id]);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
            exit;
        }
    }
    
    // Edit Project
    if ($action === 'edit') {
        $id = $input['id'] ?? null;
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $color = trim($input['color'] ?? '#3b82f6');
        $deadline = trim($input['deadline'] ?? null);
        
        if (!$id || !has_project_access($id, 'Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień do edycji tego projektu.']);
            exit;
        }
        
        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nazwa projektu jest wymagana.']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, color = ?, deadline = ? WHERE id = ?");
        $stmt->execute([$name, $description, $color, $deadline ?: null, $id]);
        
        log_activity($user_id, 'project_edit', 'Updated project details for project ID ' . $id);
        echo json_encode(['success' => true]);
        exit;
    }

    // Archive Project
    if ($action === 'archive') {
        $id = $input['id'] ?? null;
        
        if (!$id || !has_project_access($id, 'Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE projects SET is_archived = 1 WHERE id = ?");
        $stmt->execute([$id]);
        
        log_activity($user_id, 'project_archive', 'Archived project ID ' . $id);
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Restore archived project
    if ($action === 'restore') {
        $id = (int)($input['id'] ?? 0);
        if (!$id || !has_project_access($id, 'Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }
        $db->prepare("UPDATE projects SET is_archived = 0 WHERE id = ?")->execute([$id]);
        log_activity($user_id, 'project_restore', 'Restored archived project ID ' . $id);
        echo json_encode(['success' => true]);
        exit;
    }

    // Permanently delete project
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        // Only Owner can permanently delete
        $check = $db->prepare("SELECT created_by FROM projects WHERE id = ? AND is_archived = 1");
        $check->execute([$id]);
        $proj = $check->fetch();
        if (!$proj || ($_SESSION['user_role'] !== 'Owner' && $proj['created_by'] != $user_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień do trwałego usunięcia.']);
            exit;
        }
        $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
        log_activity($user_id, 'project_delete', 'Permanently deleted project ID ' . $id);
        echo json_encode(['success' => true]);
        exit;
    }

    // Add Member
    if ($action === 'add_member') {
        $project_id = $input['project_id'] ?? null;
        $email = trim($input['email'] ?? '');
        $role = $input['role'] ?? 'Member';
        
        if (!$project_id || !has_project_access($project_id, 'Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }
        
        // Find user by email
        $stmt_user = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_user->execute([$email]);
        $target_user = $stmt_user->fetch();
        
        if (!$target_user) {
            http_response_code(404);
            echo json_encode(['error' => 'Nie znaleziono użytkownika o tym adresie e-mail.']);
            exit;
        }
        
        try {
            $stmt = $db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
            $stmt->execute([$project_id, $target_user['id'], $role]);
            
            // Send Notification
            $proj_name_stmt = $db->prepare("SELECT name FROM projects WHERE id = ?");
            $proj_name_stmt->execute([$project_id]);
            $p_name = $proj_name_stmt->fetchColumn();
            
            create_notification($target_user['id'], 'Dodano do projektu', "Zostałeś dodany do projektu '$p_name' jako $role.", 'system');
            
            log_activity($user_id, 'project_add_member', "Added $email to project $project_id");
            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Ten użytkownik jest już członkiem projektu.']);
            exit;
        }
    }
}

http_response_code(400);
echo json_encode(['error' => 'Błędne zapytanie']);
exit;
