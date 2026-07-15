<?php
// api/team.php - Team management API
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // INVITE USER
    if ($action === 'invite') {
        if ($user_role !== 'Owner' && $user_role !== 'Administrator') {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }

        $email = trim($input['email'] ?? '');
        $role = $input['role'] ?? 'Member';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Podaj prawidłowy e-mail.']);
            exit;
        }

        if (!in_array($role, ['Member', 'Administrator'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowa rola.']);
            exit;
        }

        try {
            $token = bin2hex(random_bytes(32));
            $stmt = $db->prepare("INSERT INTO workspace_invites (token, email, role, invited_by, expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))");
            $stmt->execute([$token, $email, $role, $user_id]);

            log_activity($user_id, 'user_invited', "Invited $email as $role");
            echo json_encode(['success' => true, 'token' => $token]);
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
            exit;
        }
    }

    // PROMOTE USER
    if ($action === 'promote') {
        if ($user_role !== 'Owner') {
            http_response_code(403);
            echo json_encode(['error' => 'Tylko właściciel może promować.']);
            exit;
        }

        $target_id = (int)($input['user_id'] ?? 0);
        if (!$target_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID użytkownika.']);
            exit;
        }

        try {
            $stmt = $db->prepare("UPDATE users SET role = 'Administrator' WHERE id = ? AND id != ?");
            $stmt->execute([$target_id, $user_id]);

            log_activity($user_id, 'user_promoted', "Promoted user ID $target_id to Administrator");
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }

    // REMOVE USER
    if ($action === 'remove') {
        if ($user_role !== 'Owner') {
            http_response_code(403);
            echo json_encode(['error' => 'Tylko właściciel może usuwać użytkowników.']);
            exit;
        }

        $target_id = (int)($input['user_id'] ?? 0);
        if (!$target_id || $target_id === $user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Nie możesz usunąć siebie.']);
            exit;
        }

        try {
            $db->prepare("UPDATE users SET status = 'Blocked' WHERE id = ?")->execute([$target_id]);
            log_activity($user_id, 'user_removed', "Removed user ID $target_id");
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
