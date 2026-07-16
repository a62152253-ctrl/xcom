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

        <?php if ($tab === 'profile'): require_once __DIR__ . '/../includes/components/settings_profile.php'; ?>
<?php elseif ($tab === 'security'): require_once __DIR__ . '/../includes/components/settings_security.php'; ?>
<?php elseif ($tab === 'notifications'): require_once __DIR__ . '/../includes/components/settings_notifications.php'; ?>
<?php elseif ($tab === 'appearance'): require_once __DIR__ . '/../includes/components/settings_appearance.php'; endif; ?>
</div>
</div>

<script src="/assets/js/settings.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
