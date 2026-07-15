<?php
// api/notes.php - CRUD operations for notes
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // CREATE
    if ($action === 'create') {
        $title = trim($input['title'] ?? 'Bez tytułu');
        $content = trim($input['content'] ?? '');
        $color = trim($input['color'] ?? '#3b82f6');
        $tags = trim($input['tags'] ?? '');
        $is_pinned = (int)($input['is_pinned'] ?? 0);

        if (strlen($title) > 255 || strlen($content) > 10000) {
            http_response_code(400);
            echo json_encode(['error' => 'Dane za długie.']);
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO notes (user_id, title, content, color, tags, is_pinned) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $content, $color, $tags, $is_pinned]);
            
            log_activity($user_id, 'note_create', "Created note: $title");
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            error_log("Note creation error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }

    // UPDATE
    if ($action === 'update') {
        $id = (int)($input['id'] ?? 0);
        $title = trim($input['title'] ?? 'Bez tytułu');
        $content = trim($input['content'] ?? '');
        $color = trim($input['color'] ?? '#3b82f6');
        $tags = trim($input['tags'] ?? '');
        $is_pinned = (int)($input['is_pinned'] ?? 0);

        if (!$id || strlen($title) > 255 || strlen($content) > 10000) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowe dane.']);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE notes SET title=?, content=?, color=?, tags=?, is_pinned=?, updated_at=NOW() WHERE id=? AND user_id=?");
            $stmt->execute([$title, $content, $color, $tags, $is_pinned, $id, $user_id]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Brak dostępu.']);
                exit;
            }
            
            log_activity($user_id, 'note_update', "Updated note ID $id");
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            error_log("Note update error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }

    // DELETE
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID.']);
            exit;
        }

        try {
            $stmt = $db->prepare("DELETE FROM notes WHERE id=? AND user_id=?");
            $stmt->execute([$id, $user_id]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Brak dostępu.']);
                exit;
            }
            
            log_activity($user_id, 'note_delete', "Deleted note ID $id");
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            error_log("Note deletion error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }
}

http_response_code(400);
echo json_encode(['error' => 'Błędne żądanie.']);
exit;
