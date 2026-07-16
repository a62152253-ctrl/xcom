<?php
// auth/register.php - FIX: validate input lengths, require stronger password
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

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
    if (strlen($invite_token) > 255) {
        $error = 'Link zaproszenia jest nieprawidłowy.';
        $invite_token = '';
    } else {
        try {
            $db_pre = Database::getInstance()->getConnection();
            $stmt_inv = $db_pre->prepare("SELECT * FROM workspace_invites WHERE token = ? AND status = 'pending' AND expires_at > NOW()");
            $stmt_inv->execute([$invite_token]);
            $invite_data = $stmt_inv->fetch();
            if (!$invite_data) {
                $error = 'Link zaproszenia jest nieważny lub wygasł.';
                $invite_token = '';
            }
        } catch (Exception $e) {
            error_log("Invite token validation error: " . $e->getMessage());
            $error = 'Błąd walidacji zaproszenia.';
            $invite_token = '';
        }
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
    } else if (strlen($full_name) > 255) {
        $error = 'Imię i nazwisko jest za długie (max 255 znaków).';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Niepoprawny format adresu e-mail.';
    } else if (strlen($email) > 255) {
        $error = 'Adres e-mail jest za długi.';
    } else if (strlen($password) < 8) {
        $error = 'Hasło musi mieć co najmniej 8 znaków.';
    } else if (strlen($password) > 255) {
        $error = 'Hasło jest za długie.';
    } else if ($password !== $confirm_password) {
        $error = 'Hasła nie pasują do siebie.';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if user already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Adres e-mail jest już zarejestrowany.';
            } else {
                $db->beginTransaction();
                
                // Hash password with PASSWORD_ARGON2ID for better security
                $password_hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);
                
                // Insert User
                $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, role, status) VALUES (?, ?, ?, 'Member', 'Active')");
                $stmt->execute([$email, $password_hash, $full_name]);
                
                $user_id = $db->lastInsertId();
                
                // Insert User Settings
                $stmt_settings = $db->prepare("INSERT INTO settings (user_id, theme, language, email_notifications) VALUES (?, 'dark', 'pl', 1)");
                $stmt_settings->execute([$user_id]);
                
                // Handle workspace invite if present
                if ($invite_data) {
                    $db->prepare("UPDATE workspace_invites SET status = 'accepted' WHERE token = ?")->execute([$invite_token]);
                    // Apply role from invite only if it's Administrator
                    if ($invite_data['role'] === 'Administrator') {
                        $db->prepare("UPDATE users SET role = 'Administrator' WHERE id = ?")->execute([$user_id]);
                    }
                    log_activity($user_id, 'workspace_join', 'Joined workspace via invite from user ID ' . (int)$invite_data['invited_by']);
                }
                
                $db->commit();
                
                $success = 'Konto zostało pomyślnie utworzone! Możesz się teraz zalogować.';
                log_activity($user_id, 'register', 'New user registered');
            }
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Registration error: " . $e->getMessage());
            $error = 'Wystąpił błąd podczas rejestracji. Spróbuj ponownie.';
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
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/assets/js/auth.js" defer></script>
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
                <p class="auth-subtitle">Dołącz do nas i zarządzaj swoimi zadaniami z łatwością.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                
                <div class="form-group">
                    <label class="form-label" for="full_name">Imię i nazwisko</label>
                    <input class="form-control" type="text" id="full_name" name="full_name" placeholder="Jan Kowalski" required value="<?= htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" maxlength="255">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input class="form-control" type="email" id="email" name="email" placeholder="twoj@email.com" required
                    value="<?= htmlspecialchars($_POST['email'] ?? ($invite_data['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                    <?php echo $invite_data ? 'readonly style="background:var(--bg-tertiary)"' : ''; ?>" maxlength="255">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Hasło (minimum 8 znaków)</label>
                    <div class="pwd-toggle-wrap">
                        <input class="form-control" type="password" id="password" name="password" placeholder="Min. 8 znaków" required maxlength="255">
                        <button type="button" class="pwd-toggle-btn" tabindex="-1">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div id="pwd-strength-container" class="pwd-strength-meter">
                        <div class="pwd-strength-bar"></div>
                        <div class="pwd-strength-bar"></div>
                        <div class="pwd-strength-bar"></div>
                        <div class="pwd-strength-bar"></div>
                    </div>
                    <div id="pwd-strength-text" class="pwd-strength-text"></div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" for="confirm_password">Powtórz hasło</label>
                    <div class="pwd-toggle-wrap">
                        <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required maxlength="255">
                        <button type="button" class="pwd-toggle-btn" tabindex="-1">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit" style="width: 100%;">Zarejestruj się</button>
                <p style="text-align: center; font-size: 0.8rem; color: var(--text-muted); margin-top: 1rem;">Rejestracja oznacza akceptację regulaminu.</p>
            </form>

            <div class="social-login-divider">LUB ZAREJESTRUJ PRZEZ</div>

            <div class="social-login-grid">
                <button type="button" class="btn-social" onclick="window.location.href='#'">
                    <i class="fa-brands fa-google" style="color: #DB4437;"></i> Google
                </button>
                <button type="button" class="btn-social" onclick="window.location.href='#'">
                    <i class="fa-brands fa-github"></i> GitHub
                </button>
            </div>

            <div class="auth-footer">
                Masz już konto? <a class="auth-link" href="login.php">Zaloguj się</a>
            </div>
        </div>
    </div>
</body>
</html>
