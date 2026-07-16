<?php
// api/team.php – Team management API (extended)
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db        = Database::getInstance()->getConnection();
$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

/* ════════════════════════════════════════════════════════════════════════════
   GET — search users not yet in workspace
   ════════════════════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'search') {
    if ($user_role !== 'Owner' && $user_role !== 'Administrator') {
        http_response_code(403);
        echo json_encode(['error' => 'Brak uprawnień.']);
        exit;
    }

    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode(['users' => []]);
        exit;
    }

    // Search by full_name or email, exclude already active members
    $stmt = $db->prepare("
        SELECT id, full_name, email, role
        FROM users
        WHERE status = 'Active'
          AND (full_name LIKE ? OR email LIKE ?)
        ORDER BY full_name
        LIMIT 10
    ");
    $like = '%' . $q . '%';
    $stmt->execute([$like, $like]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['users' => $users]);
    exit;
}

/* ════════════════════════════════════════════════════════════════════════════
   POST handlers
   ════════════════════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    /* ── ADD USER (by ID, already registered) ───────────────────────────── */
    if ($action === 'add') {
        if ($user_role !== 'Owner' && $user_role !== 'Administrator') {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }

        $target_id = (int)($input['user_id'] ?? 0);
        $role      = $input['role'] ?? 'Member';

        if (!$target_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID użytkownika.']);
            exit;
        }
        if (!in_array($role, ['Member', 'Administrator'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowa rola.']);
            exit;
        }

        try {
            // Ensure user exists and is not already Active with a role
            $stmt = $db->prepare("SELECT id, full_name, status FROM users WHERE id = ?");
            $stmt->execute([$target_id]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                http_response_code(404);
                echo json_encode(['error' => 'Użytkownik nie istnieje.']);
                exit;
            }

            // Activate user and set role
            $db->prepare("UPDATE users SET status = 'Active', role = ? WHERE id = ?")
               ->execute([$role, $target_id]);

            log_activity($user_id, 'user_added', "Added {$target['full_name']} as $role");
            create_notification($target_id, 'Dodano do workspace', "Zostałeś dodany do workspace jako $role.", 'success');

            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
            exit;
        }
    }

    /* ── INVITE USER (by email) ──────────────────────────────────────────── */
    if ($action === 'invite') {
        if ($user_role !== 'Owner' && $user_role !== 'Administrator') {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }

        $email = trim($input['email'] ?? '');
        $role  = $input['role'] ?? 'Member';

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
            $stmt = $db->prepare("
                INSERT INTO workspace_invites (token, email, role, invited_by, expires_at)
                VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))
            ");
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

    /* ── PROMOTE (Member → Administrator) ───────────────────────────────── */
    if ($action === 'promote') {
        if ($user_role !== 'Owner') {
            http_response_code(403);
            echo json_encode(['error' => 'Tylko właściciel może promować.']);
            exit;
        }

        $target_id = (int)($input['user_id'] ?? 0);
        if (!$target_id || $target_id === $user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowe ID.']);
            exit;
        }

        try {
            $db->prepare("UPDATE users SET role = 'Administrator' WHERE id = ? AND id != ?")
               ->execute([$target_id, $user_id]);

            log_activity($user_id, 'user_promoted', "Promoted user ID $target_id to Administrator");
            create_notification($target_id, 'Zmiana roli', 'Zostałeś awansowany na Administratora!', 'success');
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }

    /* ── DEMOTE (Administrator → Member) ────────────────────────────────── */
    if ($action === 'demote') {
        if ($user_role !== 'Owner') {
            http_response_code(403);
            echo json_encode(['error' => 'Tylko właściciel może zmieniać role.']);
            exit;
        }

        $target_id = (int)($input['user_id'] ?? 0);
        if (!$target_id || $target_id === $user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowe ID.']);
            exit;
        }

        try {
            $db->prepare("UPDATE users SET role = 'Member' WHERE id = ? AND id != ?")
               ->execute([$target_id, $user_id]);

            log_activity($user_id, 'role_changed', "Demoted user ID $target_id to Member");
            create_notification($target_id, 'Zmiana roli', 'Twoja rola została zmieniona na Member.', 'info');
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }

    /* ── REMOVE USER (block) ─────────────────────────────────────────────── */
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
            $db->prepare("UPDATE users SET status = 'Blocked' WHERE id = ?")
               ->execute([$target_id]);
            log_activity($user_id, 'user_removed', "Removed user ID $target_id");
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }

    /* ── CANCEL INVITE ───────────────────────────────────────────────────── */
    if ($action === 'cancel_invite') {
        if ($user_role !== 'Owner' && $user_role !== 'Administrator') {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }

        $invite_id = (int)($input['invite_id'] ?? 0);
        if (!$invite_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID zaproszenia.']);
            exit;
        }

        try {
            $db->prepare("UPDATE workspace_invites SET status = 'expired' WHERE id = ?")
               ->execute([$invite_id]);
            log_activity($user_id, 'invite_cancelled', "Cancelled invite ID $invite_id");
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera.']);
            exit;
        }
    }

    /* ── CREATE NEW USER ACCOUNT ─────────────────────────────────────────── */
    if ($action === 'create') {
        if ($user_role !== 'Owner' && $user_role !== 'Administrator') {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }

        $full_name = trim($input['name']     ?? '');
        $email     = trim($input['email']    ?? '');
        $password  = $input['password']      ?? '';
        $role      = $input['role']          ?? 'Member';

        if (!$full_name || !$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Podaj imię, e-mail i hasło.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowy adres e-mail.']);
            exit;
        }
        if (strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Hasło musi mieć co najmniej 8 znaków.']);
            exit;
        }
        if (!in_array($role, ['Member', 'Administrator'])) {
            $role = 'Member';
        }

        try {
            // Check if email already exists
            $check = $db->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Użytkownik z tym adresem e-mail już istnieje.']);
                exit;
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("
                INSERT INTO users (full_name, email, password_hash, role, status)
                VALUES (?, ?, ?, ?, 'Active')
            ");
            $stmt->execute([$full_name, $email, $hash, $role]);
            $new_id = (int)$db->lastInsertId();

            // Create default settings for new user
            try {
                $db->prepare("INSERT INTO settings (user_id, theme, language) VALUES (?, 'dark', 'pl')")
                   ->execute([$new_id]);
            } catch (\Exception $ignored) {}

            log_activity($user_id, 'user_added', "Created new account for $email as $role");
            create_notification($new_id, 'Witaj!', "Konto zostało utworzone przez administratora. Rola: $role.", 'success');

            echo json_encode(['success' => true, 'user_id' => $new_id]);
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
            exit;
        }
    }
}

http_response_code(400);
echo json_encode(['error' => 'Błędne żądanie.']);
exit;
