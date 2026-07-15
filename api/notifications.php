<?php
// api/notifications.php - Notification management
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET UNREAD NOTIFICATIONS
    if ($action === 'list') {
        try {
            $stmt = $db->prepare("SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$user_id]);
            $notifs = $stmt->fetchAll();

            echo json_encode(['notifications' => $notifs]);
            exit;
        } catch (Exception $e) {
            error_log("Notifications fetch error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }

    // COUNT UNREAD
    if ($action === 'unread_count') {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user_id]);
            $count = (int)$stmt->fetchColumn();

            echo json_encode(['unread_count' => $count]);
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

    // MARK AS READ
    if ($action === 'mark_read') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID.']);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);

            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }

    // MARK ALL AS READ
    if ($action === 'mark_all_read') {
        try {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user_id]);

            log_activity($user_id, 'notifications_mark_read', 'Marked all notifications as read');
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }

    // DELETE NOTIFICATION
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID.']);
            exit;
        }

        try {
            $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);

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
