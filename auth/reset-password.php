<?php
// auth/reset-password.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

start_secure_session();

$error = '';
$success = '';

$email = trim($_GET['email'] ?? '');
$token = trim($_GET['token'] ?? '');

if (empty($email) || empty($token)) {
    $error = 'Brak wymaganych parametrów resetowania.';
} else {
    $db = Database::getInstance()->getConnection();
    
    // Verify token validity
    $stmt = $db->prepare("SELECT id FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
    $stmt->execute([$email, $token]);
    $reset_request = $stmt->fetch();
    
    if (!$reset_request) {
        $error = 'Niepoprawny lub wygasły token resetowania hasła.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf($csrf_token)) {
        $error = 'Błąd weryfikacji tokenu CSRF.';
    } else if (empty($password) || empty($confirm_password)) {
        $error = 'Wypełnij oba pola hasła.';
    } else if (strlen($password) < 6) {
        $error = 'Hasło musi mieć co najmniej 6 znaków.';
    } else if ($password !== $confirm_password) {
        $error = 'Hasła nie są identyczne.';
    } else {
        try {
            $db->beginTransaction();
            
            // Hash new password
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt_update = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $stmt_update->execute([$new_hash, $email]);
            
            // Delete used reset tokens
            $stmt_delete = $db->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt_delete->execute([$email]);
            
            $db->commit();
            
            $success = 'Twoje hasło zostało pomyślnie zmienione! Możesz się zalogować.';
            
            // Log security event
            log_activity(null, 'password_reset_success', 'Password reset successfully for ' . sanitize($email));
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Wystąpił błąd: ' . $e->getMessage();
        }
    }
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
                <h2 class="auth-title">Ustaw nowe hasło</h2>
                <p class="auth-subtitle">Wprowadź nowe silne hasło dla swojego konta</p>
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

            <?php if (empty($error) && empty($success)): ?>
                <form method="POST" action="reset-password.php?email=<?php echo urlencode($email); ?>&token=<?php echo urlencode($token); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Nowe hasło</label>
                        <div class="pwd-toggle-wrap">
                            <input class="form-control" type="password" id="password" name="password" placeholder="Min. 6 znaków" required>
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
                        <label class="form-label" for="confirm_password">Powtórz nowe hasło</label>
                        <div class="pwd-toggle-wrap">
                            <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                            <button type="button" class="pwd-toggle-btn" tabindex="-1">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit" style="width: 100%;">Zapisz nowe hasło</button>
                </form>
            <?php endif; ?>

            <div class="auth-footer">
                Przejdź do <a class="auth-link" href="login.php">Logowania</a>
            </div>
        </div>
    </div>
</body>
</html>
