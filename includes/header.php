<?php
// includes/header.php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/middleware.php';

// Force authentication
require_login();

$user_id    = $_SESSION['user_id'];
$user_role  = $_SESSION['user_role'];
$user_name  = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Query active notification count
$db = Database::getInstance()->getConnection();
$notif_stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$notif_stmt->execute([$user_id]);
$unread_notifications_count = $notif_stmt->fetchColumn();

// Get active projects count for navigation
$proj_stmt = $db->prepare("SELECT p.* FROM projects p INNER JOIN project_members pm ON p.id = pm.project_id WHERE pm.user_id = ? AND p.is_archived = 0 UNION SELECT * FROM projects WHERE created_by = ? AND is_archived = 0");
$proj_stmt->execute([$user_id, $user_id]);
$nav_projects = $proj_stmt->fetchAll();

// Workspace stats for command palette / hero
$ws_members = (int)$db->query("SELECT COUNT(*) FROM users WHERE status='Active'")->fetchColumn();
$ws_projects = (int)$db->query("SELECT COUNT(*) FROM projects WHERE is_archived=0")->fetchColumn();

// Determine current page basename
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['user_language'] ?? 'pl'; ?>" data-theme="<?php echo $_SESSION['user_theme'] ?? 'dark'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/premium.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- FullCalendar CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .notification-dropdown {
            position: absolute;
            top: 60px;
            right: 2rem;
            width: 340px;
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            display: none;
            flex-direction: column;
            z-index: 999;
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-dropdown.active { display: flex; }
        .notif-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.85rem;
            cursor: pointer;
            transition: var(--transition);
        }
        .notif-item:hover { background-color: var(--primary-light); }
        .notif-item.unread { font-weight: 600; background-color: rgba(59,130,246,.05); }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- ═══ SIDEBAR ══════════════════════════════════════════════════════ -->
        <?php require_once __DIR__ . '/components/sidebar.php'; ?>


        <!-- ═══ MAIN CONTENT ═════════════════════════════════════════════════ -->
        <div class="main-content">
            <!-- Topbar -->
            <?php require_once __DIR__ . '/components/topbar.php'; ?>


            <!-- Notifications Dropdown -->
            <div class="notification-dropdown" id="notif-dropdown">
                <div style="padding:1rem;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center">
                    <strong style="font-size:.9rem">Powiadomienia</strong>
                    <span style="font-size:.75rem;color:var(--primary);cursor:pointer" onclick="markAllNotificationsAsRead()">Oznacz jako przeczytane</span>
                </div>
                <div id="notif-list-container"></div>
            </div>

            <!-- ═══ COMMAND PALETTE ═══════════════════════════════════════════ -->
            <?php require_once __DIR__ . '/components/command_palette.php'; ?>


            <!-- Content Area -->
            <main class="content-body">
