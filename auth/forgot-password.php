<?php
// auth/forgot-password.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

start_secure_session();

$error = '';
$success = '';
$reset_link_debug = ''; // For debugging purposes since SMTP might not be set up

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!validate_csrf($csrf_token)) {
        $error = 'Błąd weryfikacji tokenu CSRF.';
    } else if (empty($email)) {
        $error = 'Wprowadź adres e-mail.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Niepoprawny format adresu e-mail.';
    } else {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save to DB
            $stmt_reset = $db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt_reset->execute([$email, $token, $expires]);
            
            $success = 'Wysłaliśmy link resetujący hasło na Twój e-mail.';
            
            // Direct link generation for local development/testing without mailer
            $reset_link_debug = "/auth/reset-password.php?email=" . urlencode($email) . "&token=" . $token;
            
            log_activity($user['id'], 'password_reset_request', 'Requested password reset link');
        } else {
            // Do not reveal if the user exists for security reasons, but log it
            $success = 'Wysłaliśmy link resetujący hasło na Twój e-mail.';
            log_activity(null, 'password_reset_failed', 'Requested password reset for non-existent: ' . sanitize($email));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zapomniałem hasła | TaskManager Pro</title>
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
                <h2 class="auth-title">Odzyskaj hasło</h2>
                <p class="auth-subtitle">Wprowadź swój e-mail, aby zresetować hasło</p>
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

            <?php if (!empty($reset_link_debug)): ?>
                <div class="alert alert-success" style="border: 2px dashed var(--success); font-family: monospace; font-size: 0.8rem; word-break: break-all;">
                    <strong>[MOCK MAIL / DEV MODE]</strong><br>
                    Reset link:<br>
                    <a href="<?php echo $reset_link_debug; ?>" style="color: var(--primary); text-decoration: underline;"><?php echo $reset_link_debug; ?></a>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot-password.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label" for="email">E-mail</label>
                    <input class="form-control" type="email" id="email" name="email" placeholder="twoj@email.com" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                </div>

                <button class="btn btn-primary" type="submit" style="width: 100%;">Wyślij link</button>
            </form>

            <div class="auth-footer">
                Przypomniałeś sobie? <a class="auth-link" href="login.php">Zaloguj się</a>
            </div>
        </div>
    </div>
</body>
</html>
