<?php
// api/team.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/middleware.php';
require_login();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // === Invite user to workspace ===
    if ($action === 'invite') {
        if (!in_array($user_role, ['Owner', 'Administrator'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień do zapraszania użytkowników.']);
            exit;
        }

        $email = strtolower(trim($input['email'] ?? ''));
        $role  = in_array($input['role'] ?? '', ['Administrator', 'Member']) ? $input['role'] : 'Member';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowy adres e-mail.']);
            exit;
        }

        // Check if user already exists
        $exists = $db->prepare("SELECT id FROM users WHERE email = ?");
        $exists->execute([$email]);
        if ($exists->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Użytkownik z tym adresem e-mail już istnieje w systemie.']);
            exit;
        }

        // Check pending invite
        $pending = $db->prepare("SELECT id FROM workspace_invites WHERE email = ? AND status = 'pending' AND expires_at > NOW()");
        $pending->execute([$email]);
        if ($pending->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Zaproszenie dla tego e-maila już oczekuje.']);
            exit;
        }

        // Generate unique token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+72 hours'));

        $stmt = $db->prepare("INSERT INTO workspace_invites (invited_by, email, token, role, expires_at) VALUES (?,?,?,?,?)");
        $stmt->execute([$user_id, $email, $token, $role, $expires]);

        // Build invite link (for display/copy — email sending requires SMTP setup)
        $invite_link = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'xcom.com.pl') . '/auth/register.php?invite=' . $token;

        log_activity($user_id, 'workspace_invite', "Sent workspace invite to $email (role: $role)");

        // Try to send email if mail() is available
        $subject = 'Zaproszenie do workspace TaskManager';
        $body = "Zostałeś zaproszony do workspace TaskManager.\n\nRola: $role\n\nKliknij link, aby dołączyć (ważny 72h):\n$invite_link\n\nJeśli nie zamawiałeś tego zaproszenia, zignoruj tę wiadomość.";
        @mail($email, $subject, $body, 'From: noreply@' . ($_SERVER['HTTP_HOST'] ?? 'xcom.com.pl'));

        echo json_encode([
            'success' => true,
            'invite_link' => $invite_link,
            'message' => "Zaproszenie wysłane. Link zaproszenia: $invite_link"
        ]);
        exit;
    }

    // === Cancel invite ===
    if ($action === 'cancel_invite') {
        if (!in_array($user_role, ['Owner', 'Administrator'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Brak uprawnień.']);
            exit;
        }
        $id = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("UPDATE workspace_invites SET status = 'expired' WHERE id = ? AND invited_by = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true]);
        exit;
    }
}

// === GET: return team members JSON ===
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'members') {
    $stmt = $db->query("SELECT id, full_name, email, role, status FROM users ORDER BY full_name ASC");
    echo json_encode($stmt->fetchAll());
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
