<?php
// api/calendar.php - Calendar events API
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET EVENTS FOR MONTH
    if ($action === 'month') {
        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));

        if ($month < 1 || $month > 12) $month = date('m');
        if ($year < 2000 || $year > 2100) $year = date('Y');

        $start = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
        $end = date('Y-m-t', strtotime($start));

        try {
            $stmt = $db->prepare("SELECT * FROM calendar_events WHERE user_id = ? AND event_date BETWEEN ? AND ? ORDER BY event_date ASC");
            $stmt->execute([$user_id, $start, $end]);
            $events = $stmt->fetchAll();

            echo json_encode(['events' => $events]);
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

    // CREATE EVENT
    if ($action === 'create') {
        $title = trim($input['title'] ?? '');
        $event_date = trim($input['event_date'] ?? '');
        $event_time = trim($input['event_time'] ?? '');
        $description = trim($input['description'] ?? '');

        if (empty($title) || strlen($title) > 255 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $event_date)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowe dane.']);
            exit;
        }

        try {
            $stmt = $db->prepare("INSERT INTO calendar_events (user_id, title, event_date, event_time, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $event_date, $event_time ?: null, $description]);

            log_activity($user_id, 'calendar_event_create', "Created event: $title");
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            error_log("Event creation error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }

    // DELETE EVENT
    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID.']);
            exit;
        }

        try {
            $stmt = $db->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);

            if ($stmt->rowCount() === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Brak dostępu.']);
                exit;
            }

            log_activity($user_id, 'calendar_event_delete', "Deleted event ID $id");
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
