<?php
// api/archive.php - Archive/restore projects and tasks
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET ARCHIVED ITEMS
    if ($action === 'list') {
        $type = $_GET['type'] ?? 'projects'; // projects, tasks
        
        try {
            if ($type === 'projects') {
                $stmt = $db->prepare("
                    SELECT p.*, (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count
                    FROM projects p
                    LEFT JOIN project_members pm ON p.id = pm.project_id
                    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 1
                    ORDER BY p.updated_at DESC
                ");
            } else {
                $stmt = $db->prepare("
                    SELECT t.*, p.name as project_name
                    FROM tasks t
                    INNER JOIN projects p ON t.project_id = p.id
                    LEFT JOIN project_members pm ON p.id = pm.project_id
                    WHERE (p.created_by = ? OR pm.user_id = ?) AND t.status = 'Archived'
                    ORDER BY t.updated_at DESC
                ");
            }
            $stmt->execute([$user_id, $user_id]);
            $items = $stmt->fetchAll();

            echo json_encode(['items' => $items]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // ARCHIVE PROJECT
    if ($action === 'archive_project') {
        $id = (int)($input['id'] ?? 0);
        if (!$id || !has_project_access($id, 'Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak dostępu.']);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE projects SET is_archived = 1 WHERE id = ?");
            $stmt->execute([$id]);

            log_activity($user_id, 'project_archive', "Archived project ID $id");
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }

    // RESTORE PROJECT
    if ($action === 'restore_project') {
        $id = (int)($input['id'] ?? 0);
        if (!$id || !has_project_access($id, 'Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak dostępu.']);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE projects SET is_archived = 0 WHERE id = ?");
            $stmt->execute([$id]);

            log_activity($user_id, 'project_restore', "Restored project ID $id");
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }
}

http_response_code(400);
echo json_encode(['error' => 'Błędne żądanie.']);
exit;
