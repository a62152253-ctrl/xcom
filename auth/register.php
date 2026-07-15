<?php
// auth/register.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

start_secure_session();

if (is_logged_in()) {
    header("Location: /pages/dashboard.php");
    exit;
}

$error = '';
$success = '';
$invite_token = trim($_GET['invite'] ?? $_POST['invite_token'] ?? '');
$invite_data = null;

// Validate invite token if present
if ($invite_token) {
    $db_pre = Database::getInstance()->getConnection();
    $stmt_inv = $db_pre->prepare("SELECT * FROM workspace_invites WHERE token = ? AND status = 'pending' AND expires_at > NOW()");
    $stmt_inv->execute([$invite_token]);
    $invite_data = $stmt_inv->fetch();
    if (!$invite_data) {
        $error = 'Link zaproszenia jest nieważny lub wygasł.';
        $invite_token = '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf($csrf_token)) {
        $error = 'Błąd weryfikacji tokenu CSRF.';
    } else if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Wszystkie pola są wymagane.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Niepoprawny format adresu e-mail.';
    } else if (strlen($password) < 6) {
        $error = 'Hasło musi mieć co najmniej 6 znaków.';
    } else if ($password !== $confirm_password) {
        $error = 'Hasła nie pasują do siebie.';
    } else {
        $db = Database::getInstance()->getConnection();
        
        // Check if user already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Adres e-mail jest już zarejestrowany.';
        } else {
            try {
                $db->beginTransaction();
                
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert User
                $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, role, status) VALUES (?, ?, ?, 'Member', 'Active')");
                $stmt->execute([$email, $password_hash, $full_name]);
                
                $user_id = $db->lastInsertId();
                
                // Insert User Settings
                $stmt_settings = $db->prepare("INSERT INTO settings (user_id, theme, language, email_notifications) VALUES (?, 'dark', 'pl', 1)");
                $stmt_settings->execute([$user_id]);
                
                $db->commit();
                
                // Accept workspace invite if present
                if ($invite_data) {
                    $db->prepare("UPDATE workspace_invites SET status = 'accepted' WHERE token = ?")->execute([$invite_token]);
                    // Apply role from invite
                    if ($invite_data['role'] === 'Administrator') {
                        $db->prepare("UPDATE users SET role = 'Administrator' WHERE id = ?")->execute([$user_id]);
                    }
                    log_activity($user_id, 'workspace_join', 'Joined workspace via invite from user ID ' . $invite_data['invited_by']);
                }
                
                $success = 'Konto zostało pomyślnie utworzone! Możesz się teraz zalogować.';
                log_activity($user_id, 'register', 'New user registered');
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Wystąpił błąd podczas rejestracji: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja | TaskManager Pro</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fa-solid fa-square-check"></i>
                    <span>TaskManager Pro</span>
                </div>
                <h2 class="auth-title">Załóż darmowe konto</h2>
                <p class="auth-subtitle">Zacznij zarządzać zadaniami efektywnie</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo sanitize($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo sanitize($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="full_name">Imię i nazwisko</label>
                    <input class="form-control" type="text" id="full_name" name="full_name" placeholder="Jan Kowalski" required value="<?php echo isset($_POST['full_name']) ? sanitize($_POST['full_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input class="form-control" type="email" id="email" name="email" placeholder="twoj@email.com" required
                    value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ($invite_data ? sanitize($invite_data['email']) : ''); ?>"
                    <?php echo $invite_data ? 'readonly style="background:var(--bg-tertiary)"' : ''; ?>>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Hasło</label>
                    <input class="form-control" type="password" id="password" name="password" placeholder="Min. 6 znaków" required>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" for="confirm_password">Powtórz hasło</label>
                    <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                </div>

                <button class="btn btn-primary" type="submit">Zarejestruj się</button>
            </form>

            <div class="auth-footer">
                Masz już konto? <a class="auth-link" href="login.php">Zaloguj się</a>
            </div>
        </div>
    </div>
</body>
</html>
