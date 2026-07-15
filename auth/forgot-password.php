<?php
// auth/forgot-password.php - Secure password reset flow
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
$step = $_GET['step'] ?? 'request'; // request | reset

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf($csrf_token)) {
        $error = 'Błąd weryfikacji tokenu CSRF.';
    } else if ($step === 'request') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Podaj adres e-mail.';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Niepoprawny format adresu e-mail.';
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Generate secure token
                    $reset_token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                    
                    // Store token
                    $stmt_update = $db->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
                    $stmt_update->execute([$reset_token, $expires_at, $user['id']]);
                    
                    // TODO: Send email with reset link
                    // send_email($email, 'Reset hasła', "Kliknij: /auth/forgot-password.php?step=reset&token=$reset_token");
                    
                    log_activity($user['id'], 'password_reset_request', 'Password reset requested');
                    $success = 'Jeśli konto istnieje, wyślemy Ci link do resetowania hasła.';
                } else {
                    $success = 'Jeśli konto istnieje, wyślemy Ci link do resetowania hasła.';
                }
            } catch (Exception $e) {
                error_log("Password reset request error: " . $e->getMessage());
                $error = 'Błąd podczas przetwarzania żądania.';
            }
        }
    } else if ($step === 'reset') {
        $token = trim($_POST['token'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        if (empty($token) || strlen($token) > 255) {
            $error = 'Niepoprawny token.';
        } else if (empty($password) || empty($confirm_password)) {
            $error = 'Wszystkie pola są wymagane.';
        } else if (strlen($password) < 8) {
            $error = 'Hasło musi mieć co najmniej 8 znaków.';
        } else if ($password !== $confirm_password) {
            $error = 'Hasła nie pasują do siebie.';
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                
                // Verify token
                $stmt = $db->prepare("SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
                $stmt->execute([$token]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    $error = 'Link jest nieważny lub wygasł.';
                } else {
                    // Update password
                    $password_hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);
                    
                    $stmt_update = $db->prepare("UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
                    $stmt_update->execute([$password_hash, $user['id']]);
                    
                    log_activity($user['id'], 'password_reset_success', 'Password successfully reset');
                    $success = 'Hasło zostało zmienione. Możesz się teraz zalogować.';
                    $step = 'request';
                }
            } catch (Exception $e) {
                error_log("Password reset error: " . $e->getMessage());
                $error = 'Błąd podczas resetowania hasła.';
            }
        }
    }
}

// For reset step, verify token exists
$reset_token = trim($_GET['token'] ?? '');
if ($step === 'reset' && empty($reset_token)) {
    $error = 'Brakuje tokenu resetowania.';
}
?>
<!DOCTYPE html>
<html lang="pl" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetowanie hasła | TaskManager Pro</title>
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
                <h2 class="auth-title">
                    <?php echo $step === 'reset' ? 'Ustaw nowe hasło' : 'Resetuj hasło'; ?>
                </h2>
                <p class="auth-subtitle">
                    <?php echo $step === 'reset' ? 'Wpisz nowe hasło poniżej.' : 'Wpisz adres e-mail, aby otrzymać link do resetowania hasła.'; ?>
                </p>
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

            <?php if ($step === 'request'): ?>
            <form method="POST" action="forgot-password.php?step=request">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="form-group">
                    <label class="form-label" for="email">Adres e-mail</label>
                    <input class="form-control" type="email" id="email" name="email" placeholder="twoj@email.com" required maxlength="255" value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                </div>

                <button class="btn btn-primary" type="submit" style="width: 100%;">
                    <i class="fa-solid fa-paper-plane"></i> Wyślij link resetowania
                </button>
            </form>

            <?php elseif ($step === 'reset' && !empty($reset_token)): ?>
            <form method="POST" action="<?php echo htmlspecialchars('forgot-password.php?step=reset&token=' . $reset_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($reset_token, ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="form-group">
                    <label class="form-label" for="password">Nowe hasło (minimum 8 znaków)</label>
                    <div class="pwd-toggle-wrap">
                        <input class="form-control" type="password" id="password" name="password" placeholder="Min. 8 znaków" required maxlength="255">
                        <button type="button" class="pwd-toggle-btn" tabindex="-1">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" for="confirm_password">Potwierdź hasło</label>
                    <div class="pwd-toggle-wrap">
                        <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required maxlength="255">
                        <button type="button" class="pwd-toggle-btn" tabindex="-1">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit" style="width: 100%;">
                    <i class="fa-solid fa-lock"></i> Ustaw nowe hasło
                </button>
            </form>
            <?php else: ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-exclamation-circle"></i> Link resetowania jest nieważny lub wygasł. Spróbuj ponownie.
            </div>
            <a href="forgot-password.php" class="btn btn-primary" style="width: 100%; text-align: center;">
                Wróć do formularza resetowania
            </a>
            <?php endif; ?>

            <div class="auth-footer" style="margin-top: 2rem;">
                Pamiętasz hasło? <a class="auth-link" href="login.php">Zaloguj się</a>
            </div>
        </div>
    </div>
</body>
</html>
