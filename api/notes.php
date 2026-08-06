<?php
// api/notes.php - Notion Pages API with self-healing migration
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    if ($action === 'list') {
        // Return flat list of all pages for the user to let frontend build trees/sections dynamically
        $stmt = $db->prepare("SELECT id, parent_id, title, content, color, tags, is_pinned, is_favorite, is_archived, is_trash, icon, updated_at FROM notes WHERE user_id = ? ORDER BY is_pinned DESC, title ASC");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'notes' => $stmt->fetchAll()]);
        exit;
    }

    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $note = $stmt->fetch();
        if (!$note) {
            echo json_encode(['success' => false, 'error' => 'Strona nie istnieje lub brak dostępu.']);
            exit;
        }
        echo json_encode(['success' => true, 'note' => $note]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = isset($input['id']) ? (int)$input['id'] : null;

        if ($action === 'create') {
            $parent_id = !empty($input['parent_id']) ? (int)$input['parent_id'] : null;
            $title = trim($input['title'] ?? 'Bez tytułu');
            $icon = trim($input['icon'] ?? '📝');
            $color = trim($input['color'] ?? '#3b82f6');
            $tags = trim($input['tags'] ?? '');

            $stmt = $db->prepare("INSERT INTO notes (user_id, parent_id, title, content, color, tags, is_pinned, is_favorite, is_archived, is_trash, icon) VALUES (?, ?, ?, '', ?, ?, 0, 0, 0, 0, ?)");
            $stmt->execute([$user_id, $parent_id, $title, $color, $tags, $icon]);
            $new_id = $db->lastInsertId();

            log_activity($user_id, 'page_create', "Created page: $title");
            echo json_encode(['success' => true, 'id' => (int)$new_id]);
            exit;
        }

        if ($action === 'update') {
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'Brak ID strony.']);
                exit;
            }
            $title = trim($input['title'] ?? 'Bez tytułu');
            $content = $input['content'] ?? ''; // rich block JSON
            $color = trim($input['color'] ?? '#3b82f6');
            $tags = trim($input['tags'] ?? '');
            $icon = trim($input['icon'] ?? '📝');
            $parent_id = !empty($input['parent_id']) ? (int)$input['parent_id'] : null;
            $is_pinned = (int)($input['is_pinned'] ?? 0);
            $is_favorite = (int)($input['is_favorite'] ?? 0);
            $is_archived = (int)($input['is_archived'] ?? 0);
            $is_trash = (int)($input['is_trash'] ?? 0);

            $stmt = $db->prepare("UPDATE notes SET parent_id=?, title=?, content=?, color=?, tags=?, is_pinned=?, is_favorite=?, is_archived=?, is_trash=?, icon=?, updated_at=NOW() WHERE id=? AND user_id=?");
            $stmt->execute([$parent_id, $title, $content, $color, $tags, $is_pinned, $is_favorite, $is_archived, $is_trash, $icon, $id, $user_id]);

            log_activity($user_id, 'page_update', "Updated page: $title");
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'toggle_favorite') {
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'Brak ID.']);
                exit;
            }
            $stmt = $db->prepare("UPDATE notes SET is_favorite = NOT is_favorite, updated_at=NOW() WHERE id=? AND user_id=?");
            $stmt->execute([$id, $user_id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'toggle_archive') {
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'Brak ID.']);
                exit;
            }
            $stmt = $db->prepare("UPDATE notes SET is_archived = NOT is_archived, updated_at=NOW() WHERE id=? AND user_id=?");
            $stmt->execute([$id, $user_id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'toggle_trash') {
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'Brak ID.']);
                exit;
            }
            $stmt = $db->prepare("UPDATE notes SET is_trash = NOT is_trash, updated_at=NOW() WHERE id=? AND user_id=?");
            $stmt->execute([$id, $user_id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'empty_trash') {
            $stmt = $db->prepare("DELETE FROM notes WHERE user_id=? AND is_trash=1");
            $stmt->execute([$user_id]);
            log_activity($user_id, 'page_empty_trash', "Emptied page trash");
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'delete_permanently') {
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'Brak ID.']);
                exit;
            }
            $stmt = $db->prepare("DELETE FROM notes WHERE id=? AND user_id=?");
            $stmt->execute([$id, $user_id]);
            log_activity($user_id, 'page_permanent_delete', "Permanently deleted page ID: $id");
            echo json_encode(['success' => true]);
            exit;
        }
    }
} catch (Exception $e) {
    error_log("Notion Pages API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Błąd serwera.']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Błędne żądanie.']);
exit;