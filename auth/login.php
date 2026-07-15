<?php
// auth/login.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ratelimit.php';
require_once __DIR__ . '/../config/env.php';

start_secure_session();

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: /pages/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Rate limiting by IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ratelimit = new RateLimit(Database::getInstance()->getConnection());
    if (!$ratelimit->check($ip, 'login', 5, 300)) {
        $error = 'Zbyt wiele prób logowania. Spróbuj ponownie za 5 minut.';
    }
    
    if (!$error && !validate_csrf($csrf_token)) {
        $error = 'Błąd weryfikacji tokenu CSRF.';
    } else if (!$error && empty($email) || empty($password)) {
        $error = 'Wypełnij wszystkie pola.';
    } else if (!$error) {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Niepoprawny format adresu e-mail.';
        } else {
            $db = Database::getInstance()->getConnection();
            
            // Find user
            $stmt = $db->prepare("SELECT u.*, s.theme, s.language FROM users u LEFT JOIN settings s ON u.id = s.user_id WHERE u.email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] === 'Blocked') {
                    $error = 'Twoje konto zostało zablokowane. Skontaktuj się z administratorem.';
                } else if ($user['status'] === 'Pending') {
                    $error = 'Konto nieaktywne. Proszę zweryfikować adres e-mail.';
                } else {
                    // Successful login - regenerate session and update last login
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_status'] = $user['status'];
                    $_SESSION['user_avatar'] = $user['avatar'] ?? '';
                    $_SESSION['user_theme'] = $user['theme'] ?? 'light';
                    $_SESSION['user_language'] = $user['language'] ?? 'pl';
                    
                    // Update last login timestamp
                    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                    
                    log_activity($user['id'], 'login', 'User logged in successfully');
                    
                    header("Location: /pages/dashboard.php");
                    exit;
                }
            } else {
                $error = 'Błędny e-mail lub hasło.';
                log_activity(null, 'failed_login', 'Failed login attempt for: ' . sanitize($email) . ' from ' . $ip);
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
    <title>Logowanie | TaskManager Pro</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/auth.css">
    <!-- FontAwesome Icons -->
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
                <h2 class="auth-title">Witaj ponownie</h2>
                <p class="auth-subtitle">Zaloguj się, aby kontynuować pracę</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo sanitize($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input class="form-control" type="email" id="email" name="email" placeholder="twoj@email.com" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>" autocomplete="email">
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label class="form-label" style="margin-bottom: 0;" for="password">Hasło</label>
                        <a class="auth-link" style="font-size: 0.8rem;" href="forgot-password.php">Zapomniałeś hasła?</a>
                    </div>
                    <div class="pwd-toggle-wrap">
                        <input class="form-control" type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="pwd-toggle-btn" tabindex="-1">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <div class="form-check">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Zapamiętaj mnie</label>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit" style="width: 100%;">Zaloguj się</button>
            </form>

            <div class="social-login-divider">LUB ZALOGUJ PRZEZ</div>

            <div class="social-login-grid">
                <button type="button" class="btn-social" onclick="window.location.href='#'">
                    <i class="fa-brands fa-google" style="color: #DB4437;"></i> Google
                </button>
                <button type="button" class="btn-social" onclick="window.location.href='#'">
                    <i class="fa-brands fa-github"></i> GitHub
                </button>
            </div>

            <div class="auth-footer">
                Nie masz konta? <a class="auth-link" href="register.php">Zarejestruj się</a>
            </div>
        </div>
    </div>
</body>
</html>
