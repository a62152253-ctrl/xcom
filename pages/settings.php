<?php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Settings with defaults
$stmt = $db->prepare("SELECT u.*, s.language, s.email_notifications, s.theme FROM users u LEFT JOIN settings s ON u.id = s.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();

$success = isset($_GET['success']);
$error   = $_GET['error'] ?? '';

// Tab from URL - whitelist validation
$allowed_tabs = ['profile', 'security', 'notifications', 'appearance'];
$tab = in_array($_GET['tab'] ?? '', $allowed_tabs, true) ? $_GET['tab'] : 'profile';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-sliders"></i> Ustawienia konta</h1>
        <p class="page-subtitle">Zarządzaj profilem, hasłem, powiadomieniami i wyglądem.</p>
    </div>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Ustawienia zostały zapisane!</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars(urldecode($error), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div><?php endif; ?>

<div class="settings-layout">
    <!-- Sidebar tabs -->
    <nav class="settings-nav">
        <a href="?tab=profile"       class="settings-nav-item <?= $tab === 'profile'       ? 'active' : '' ?>"><i class="fa-solid fa-user"></i> Profil</a>
        <a href="?tab=security"      class="settings-nav-item <?= $tab === 'security'      ? 'active' : '' ?>"><i class="fa-solid fa-lock"></i> Bezpieczeństwo</a>
        <a href="?tab=notifications" class="settings-nav-item <?= $tab === 'notifications' ? 'active' : '' ?>"><i class="fa-solid fa-bell"></i> Powiadomienia</a>
        <a href="?tab=appearance"    class="settings-nav-item <?= $tab === 'appearance'    ? 'active' : '' ?>"><i class="fa-solid fa-palette"></i> Wygląd</a>
    </nav>

    <!-- Tab content -->
    <div class="settings-content">

        <!-- ── PROFILE TAB ── -->
        <?php if ($tab === 'profile'): ?>
        <form method="POST" action="/api/profile.php?action=settings" enctype="multipart/form-data">
            <div class="settings-section">
                <h2 class="settings-section-title">Zdjęcie profilowe</h2>
                <div class="avatar-upload-row">
                    <?php if (!empty($user_data['avatar'])): ?>
                        <img src="<?= htmlspecialchars($user_data['avatar'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="settings-avatar">
                    <?php else: ?>
                        <div class="settings-avatar settings-avatar-placeholder"><?= htmlspecialchars(strtoupper(substr($user_data['full_name'], 0, 1)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div>
                        <label class="btn btn-secondary" style="cursor:pointer;width:auto">
                            <i class="fa-solid fa-camera"></i> Zmień zdjęcie
                            <input type="file" name="avatar" accept="image/*" style="display:none" onchange="previewAvatar(this)">
                        </label>
                        <p style="font-size:.75rem;color:var(--text-muted);margin-top:.5rem">JPG, PNG, max 2MB</p>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h2 class="settings-section-title">Dane osobowe</h2>
                <div class="form-group">
                    <label class="form-label">Imię i nazwisko</label>
                    <input class="form-control" type="text" name="full_name" value="<?= htmlspecialchars($user_data['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" required maxlength="255">
                </div>
                <div class="form-group">
                    <label class="form-label">E-mail <span style="color:var(--text-muted);font-size:.8rem">(tylko do odczytu)</span></label>
                    <input class="form-control" type="email" value="<?= htmlspecialchars($user_data['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" disabled>
                </div>
            </div>

            <div class="settings-section">
                <h2 class="settings-section-title">Język aplikacji</h2>
                <div class="form-group">
                    <select class="form-control" name="language" style="max-width:240px">
                        <option value="pl" <?= ($user_data['language'] ?? 'pl') === 'pl' ? 'selected' : '' ?>>🇵🇱 Polski</option>
                        <option value="en" <?= ($user_data['language'] ?? 'pl') === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
                    </select>
                </div>
            </div>

            <button class="btn btn-primary" type="submit" style="width:auto"><i class="fa-solid fa-floppy-disk"></i> Zapisz zmiany</button>
        </form>

        <!-- ── SECURITY TAB ── -->
        <?php elseif ($tab === 'security'): ?>
        <form method="POST" action="/api/profile.php?action=settings">
            <div class="settings-section">
                <h2 class="settings-section-title">Zmiana hasła</h2>
                <p style="color:var(--text-secondary);font-size:.875rem;margin-bottom:1.25rem">Zostaw puste jeśli nie chcesz zmieniać hasła.</p>

                <link rel="stylesheet" href="/assets/css/auth.css">
                <script src="/assets/js/auth.js" defer></script>

                <div class="form-group">
                    <label class="form-label">Nowe hasło</label>
                    <div class="pwd-toggle-wrap">
                        <input class="form-control" type="password" name="password" id="password" placeholder="Minimum 8 znaków" style="padding-right:2.5rem" maxlength="255">
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
                <div class="form-group">
                    <label class="form-label">Potwierdź nowe hasło</label>
                    <div class="pwd-toggle-wrap">
                        <input class="form-control" type="password" name="confirm_password" id="confirm_password" placeholder="Powtórz hasło" maxlength="255">
                        <button type="button" class="pwd-toggle-btn" tabindex="-1">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="settings-section">
                <h2 class="settings-section-title">Informacje o sesji</h2>
                <div class="session-info-card">
                    <i class="fa-solid fa-globe"></i>
                    <div>
                        <div style="font-weight:600;font-size:.875rem">Aktualna sesja</div>
                        <div style="color:var(--text-muted);font-size:.8rem">IP: <?= htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '—', ENT_QUOTES, 'UTF-8') ?> · <?= date('d.m.Y H:i') ?></div>
                    </div>
                    <span class="badge-pill badge-active">Aktywna</span>
                </div>
                <a href="/auth/logout.php" class="btn btn-secondary" style="width:auto;margin-top:1rem;color:var(--danger)">
                    <i class="fa-solid fa-right-from-bracket"></i> Wyloguj się ze wszystkich urządzeń
                </a>
            </div>

            <button class="btn btn-primary" type="submit" style="width:auto"><i class="fa-solid fa-lock"></i> Zmień hasło</button>
        </form>

        <!-- ── NOTIFICATIONS TAB ── -->
        <?php elseif ($tab === 'notifications'): ?>
        <form method="POST" action="/api/profile.php?action=settings">
            <div class="settings-section">
                <h2 class="settings-section-title">Powiadomienia email</h2>
                <div class="settings-toggle-row">
                    <div>
                        <div style="font-weight:600;font-size:.875rem">Powiadomienia e-mail</div>
                        <div style="color:var(--text-muted);font-size:.8rem">Otrzymuj powiadomienia o przypisanych zadaniach i komentarzach</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" name="email_notifications" <?= ($user_data['email_notifications'] ?? 1) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="settings-section">
                <h2 class="settings-section-title">Powiadomienia push</h2>
                <div class="settings-toggle-row">
                    <div>
                        <div style="font-weight:600;font-size:.875rem">Powiadomienia w przeglądarce</div>
                        <div style="color:var(--text-muted);font-size:.8rem">Natychmiastowe alerty o nowych zadaniach</div>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="requestPushPermission()" style="width:auto;font-size:.8rem" id="push-btn">
                        <i class="fa-solid fa-bell"></i> Włącz
                    </button>
                </div>
            </div>

            <button class="btn btn-primary" type="submit" style="width:auto"><i class="fa-solid fa-floppy-disk"></i> Zapisz</button>
        </form>

        <!-- ── APPEARANCE TAB ── -->
        <?php elseif ($tab === 'appearance'): ?>
        <div class="settings-section">
            <h2 class="settings-section-title">Motyw kolorystyczny</h2>
            <div class="theme-picker-grid">
                <div class="theme-option <?= ($user_data['theme'] ?? 'dark') === 'dark' ? 'theme-option--active' : '' ?>"
                     onclick="setTheme('dark')" id="theme-dark">
                    <div class="theme-preview theme-preview--dark">
                        <div class="theme-preview-sidebar"></div>
                        <div class="theme-preview-content"></div>
                    </div>
                    <div class="theme-option-label"><i class="fa-solid fa-moon"></i> Ciemny</div>
                </div>
                <div class="theme-option <?= ($user_data['theme'] ?? 'dark') === 'light' ? 'theme-option--active' : '' ?>"
                     onclick="setTheme('light')" id="theme-light">
                    <div class="theme-preview theme-preview--light">
                        <div class="theme-preview-sidebar"></div>
                        <div class="theme-preview-content"></div>
                    </div>
                    <div class="theme-option-label"><i class="fa-solid fa-sun"></i> Jasny</div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h2 class="settings-section-title">Podgląd na żywo</h2>
            <p style="color:var(--text-secondary);font-size:.875rem">Zmiany motywu są stosowane natychmiast. Nie musisz zapisywać.</p>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
// Avatar preview
function previewAvatar(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const existing = document.querySelector('.settings-avatar');
        if (existing) existing.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

// Live theme switcher
async function setTheme(theme) {
    if (!['light', 'dark'].includes(theme)) return;
    
    document.documentElement.setAttribute('data-theme', theme);
    document.querySelectorAll('.theme-option').forEach(el => el.classList.remove('theme-option--active'));
    document.getElementById('theme-' + theme)?.classList.add('theme-option--active');

    const icon = document.getElementById('theme-toggle-btn');
    if (icon) icon.innerHTML = `<i class="fa-solid ${theme === 'dark' ? 'fa-sun' : 'fa-moon'}"></i>`;

    const json = await apiPost('/api/profile.php?action=theme', { theme });
    if (json?.success) Toast.success('Motyw ' + (theme === 'dark' ? 'ciemny' : 'jasny') + ' aktywowany!');
}

// Push notifications
function requestPushPermission() {
    if (!('Notification' in window)) { Toast.warning('Twoja przeglądarka nie wspiera powiadomień push.'); return; }
    Notification.requestPermission().then(p => {
        if (p === 'granted') {
            Toast.success('Powiadomienia push włączone!');
            document.getElementById('push-btn').innerHTML = '<i class="fa-solid fa-check"></i> Włączone';
        } else {
            Toast.error('Brak zgody na powiadomienia.');
        }
    });
}

// Check existing push permission
if (window.Notification?.permission === 'granted') {
    const btn = document.getElementById('push-btn');
    if (btn) btn.innerHTML = '<i class="fa-solid fa-check"></i> Włączone';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
