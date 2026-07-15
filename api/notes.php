<?php
// api/notes.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/middleware.php';
require_login();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if ($action === 'create') {
        $title   = trim($input['title'] ?? '') ?: 'Bez tytułu';
        $content = $input['content'] ?? '';
        $tags    = trim($input['tags'] ?? '');
        $color   = preg_match('/^#[0-9a-fA-F]{6}$/', $input['color'] ?? '') ? $input['color'] : '#3b82f6';
        $pinned  = isset($input['is_pinned']) ? (int)$input['is_pinned'] : 0;

        $stmt = $db->prepare("INSERT INTO notes (user_id, title, content, color, tags, is_pinned) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$user_id, $title, $content, $color, $tags, $pinned]);
        echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        exit;
    }

    if ($action === 'update') {
        $id      = (int)($input['id'] ?? 0);
        $title   = trim($input['title'] ?? '') ?: 'Bez tytułu';
        $content = $input['content'] ?? '';
        $tags    = trim($input['tags'] ?? '');
        $color   = preg_match('/^#[0-9a-fA-F]{6}$/', $input['color'] ?? '') ? $input['color'] : '#3b82f6';
        $pinned  = isset($input['is_pinned']) ? (int)$input['is_pinned'] : 0;

        // Verify ownership
        $check = $db->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
        $check->execute([$id, $user_id]);
        if (!$check->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień']);
            exit;
        }

        $stmt = $db->prepare("UPDATE notes SET title=?, content=?, color=?, tags=?, is_pinned=? WHERE id=? AND user_id=?");
        $stmt->execute([$title, $content, $color, $tags, $pinned, $id, $user_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
