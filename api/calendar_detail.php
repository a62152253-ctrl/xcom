<?php
// api/calendar_detail.php - Get single calendar event
require_once __DIR__ . '/../includes/middleware.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Brak ID.']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM calendar_events WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $event = $stmt->fetch();

    if (!$event) {
        http_response_code(404);
        echo json_encode(['error' => 'Wydarzenie nie znalezione.']);
        exit;
    }

    echo json_encode(['event' => $event]);
    exit;
} catch (Exception $e) {
    error_log("Calendar detail error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera.']);
    exit;
}
