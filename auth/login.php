<?php
// auth/login.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

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
    
    if (!isset($_SESSION['csrf_token']) || !validate_csrf($csrf_token)) {
        $error = 'Błąd weryfikacji tokenu CSRF.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Wypełnij wszystkie pola.';
    } else {
        $db = Database::getInstance()->getConnection();
        
        // Find user
        $stmt = $db->prepare("SELECT u.*, s.theme, s.language FROM users u LEFT JOIN settings s ON u.id = s.user_id WHERE u.email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'Blocked') {
                $error = 'Twoje konto zostało zablokowane. Skontaktuj się z administratorem.';
            } elseif ($user['status'] === 'Pending') {
                $error = 'Konto nieaktywne. Proszę zweryfikować adres e-mail.';
            } else {
                // Successful login
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_status'] = $user['status'];
                $_SESSION['user_avatar'] = $user['avatar'];
                $_SESSION['user_theme'] = $user['theme'] ?? 'light';
                $_SESSION['user_language'] = $user['language'] ?? 'pl';
                
                // Remember Me
                if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
                    $selector = bin2hex(random_bytes(16));
                    $validator = bin2hex(random_bytes(32));
                    $expires = time() + 86400 * 30; // 30 days

                    $validator_hash = hash('sha256', $validator);

                    // Insert into DB
                    $stmt = $db->prepare("INSERT INTO user_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user['id'], $selector, $validator_hash, date('Y-m-d H:i:s', $expires)]);

                    // Set cookie
                    setcookie(
                        'remember_me',
                        $selector . ':' . $validator,
                        $expires,
                        '/',
                        '',
                        isset($_SERVER['HTTPS']), // Secure if HTTPS
                        true // HttpOnly
                    );
                }

                log_activity($user['id'], 'login', 'User logged in successfully');
                
                header("Location: /pages/dashboard.php");
                exit;
            }
        } else {
            $error = 'Błędny e-mail lub hasło.';
            // Log security event (anonymously or using the entered email in details)
            log_activity(null, 'failed_login', 'Failed login attempt for: ' . sanitize($email));
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
    <!-- FontAwesome Icons -->
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
                <h2 class="auth-title">Witaj ponownie</h2>
                <p class="auth-subtitle">Zaloguj się, aby kontynuować pracę</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo sanitize($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input class="form-control" type="email" id="email" name="email" placeholder="twoj@email.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label class="form-label" style="margin-bottom: 0;" for="password">Hasło</label>
                        <a class="auth-link" style="font-size: 0.8rem;" href="forgot-password.php">Zapomniałeś hasła?</a>
                    </div>
                    <div style="position: relative;">
                        <input class="form-control" style="padding-right: 40px;" type="password" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer;">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <div class="form-check">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Zapamiętaj mnie</label>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit">Zaloguj się</button>
            </form>

            <div class="auth-footer">
                Nie masz konta? <a class="auth-link" href="register.php">Zarejestruj się</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function (e) {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
