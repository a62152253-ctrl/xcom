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
        <aside class="sidebar">
            <!-- Logo -->
            <div class="sidebar-header">
                <a href="/pages/dashboard.php" class="sidebar-logo">
                    <i class="fa-solid fa-square-check"></i>
                    <span>TaskManager</span>
                </a>
            </div>

            <!-- Workspace Badge -->
            <div class="workspace-badge" onclick="openCommandPalette()" title="Ctrl+K — Command Palette">
                <div class="workspace-avatar">T</div>
                <div class="workspace-info">
                    <div class="workspace-name"><?php echo sanitize($user_name); ?>'s Workspace</div>
                    <div class="workspace-plan">Pro · <?= $ws_members ?> członków</div>
                </div>
                <i class="fa-solid fa-chevron-down workspace-chevron"></i>
            </div>

            <nav class="sidebar-nav">
                <span class="nav-section-label">Główne</span>
                <a href="/pages/dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-line"></i>
                    <span><?php echo __('dashboard'); ?></span>
                </a>
                <a href="/pages/projects.php" class="nav-item <?php echo $current_page == 'projects.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-folder-open"></i>
                    <span><?php echo __('projects'); ?></span>
                    <?php if ($ws_projects > 0): ?>
                    <span class="nav-badge" style="background:var(--primary)"><?= $ws_projects ?></span>
                    <?php endif; ?>
                </a>
                <a href="/pages/tasks.php" class="nav-item <?php echo $current_page == 'tasks.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-list-check"></i>
                    <span><?php echo __('tasks'); ?></span>
                </a>
                <a href="/pages/calendar.php" class="nav-item <?php echo $current_page == 'calendar.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span><?php echo __('calendar'); ?></span>
                </a>

                <span class="nav-section-label">Analizy</span>
                <a href="/pages/reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-bar"></i>
                    <span>Raporty</span>
                </a>
                <a href="/pages/team.php" class="nav-item <?php echo $current_page == 'team.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users"></i>
                    <span>Zespół</span>
                </a>

                <span class="nav-section-label">Zasoby</span>
                <a href="/pages/notes.php" class="nav-item <?php echo $current_page == 'notes.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-note-sticky"></i>
                    <span>Notatki</span>
                </a>
                <a href="/pages/files.php" class="nav-item <?php echo $current_page == 'files.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-folder"></i>
                    <span>Pliki</span>
                </a>
                <a href="/pages/archive.php" class="nav-item <?php echo $current_page == 'archive.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-box-archive"></i>
                    <span>Archiwum</span>
                </a>

                <span class="nav-section-label">Konto</span>
                <a href="/pages/notifications.php" class="nav-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-bell"></i>
                    <span>Powiadomienia</span>
                    <?php if ($unread_notifications_count > 0): ?>
                    <span class="nav-badge"><?php echo $unread_notifications_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="/pages/profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-gear"></i>
                    <span><?php echo __('profile'); ?></span>
                </a>
                <a href="/pages/settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-sliders"></i>
                    <span><?php echo __('settings'); ?></span>
                </a>
                <?php if ($user_role === 'Owner' || $user_role === 'Administrator'): ?>
                <a href="/pages/admin.php" class="nav-item <?php echo $current_page == 'admin.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-shield"></i>
                    <span><?php echo __('admin'); ?></span>
                </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo sanitize($user_name); ?></div>
                    <div class="user-role"><?php echo sanitize($user_role); ?></div>
                </div>
                <a href="/auth/logout.php" title="<?php echo __('logout'); ?>" style="color:var(--text-secondary)">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </aside>

        <!-- ═══ MAIN CONTENT ═════════════════════════════════════════════════ -->
        <div class="main-content">
            <!-- Topbar -->
            <header class="top-bar">
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="global-search" placeholder="Szukaj zadań, projektów..." onkeyup="handleGlobalSearch(this.value)">
                    <div id="search-results" style="display:none;position:absolute;top:45px;left:0;right:0;background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:var(--radius-md);box-shadow:var(--shadow-lg);z-index:1000;max-height:250px;overflow-y:auto;padding:.5rem"></div>
                </div>

                <div class="top-actions">
                    <!-- Command Palette hint -->
                    <div class="cmd-hint" onclick="openCommandPalette()">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span>Szukaj</span>
                        <kbd>Ctrl</kbd><kbd>K</kbd>
                    </div>

                    <!-- Quick actions -->
                    <div class="topbar-quick">
                        <button class="btn btn-primary" style="padding:5px 12px;font-size:12px" onclick="window.location.href='/pages/tasks.php'">
                            <i class="fa-solid fa-plus"></i> Zadanie
                        </button>
                        <button class="btn btn-secondary" style="padding:5px 12px;font-size:12px" onclick="window.location.href='/pages/projects.php'">
                            <i class="fa-solid fa-folder-plus"></i> Projekt
                        </button>
                    </div>

                    <!-- Theme toggle -->
                    <div class="theme-toggler" onclick="toggleTheme()" id="theme-toggle-btn">
                        <i class="fa-solid <?php echo ($_SESSION['user_theme'] ?? 'dark') === 'dark' ? 'fa-sun' : 'fa-moon'; ?>"></i>
                    </div>

                    <!-- Notification Bell -->
                    <div class="notification-badge" onclick="toggleNotificationsDropdown()">
                        <i class="fa-solid fa-bell"></i>
                        <span class="badge" id="notif-count"><?php echo $unread_notifications_count; ?></span>
                    </div>
                </div>
            </header>

            <!-- Notifications Dropdown -->
            <div class="notification-dropdown" id="notif-dropdown">
                <div style="padding:1rem;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center">
                    <strong style="font-size:.9rem">Powiadomienia</strong>
                    <span style="font-size:.75rem;color:var(--primary);cursor:pointer" onclick="markAllNotificationsAsRead()">Oznacz jako przeczytane</span>
                </div>
                <div id="notif-list-container"></div>
            </div>

            <!-- ═══ COMMAND PALETTE ═══════════════════════════════════════════ -->
            <div class="cmd-overlay" id="cmdPalette" onclick="closeCommandPalette()">
            <div class="cmd-palette" onclick="event.stopPropagation()">
                <div class="cmd-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="cmdInput" placeholder="Wpisz komendę lub wyszukaj..."
                        oninput="filterCmdItems(this.value)" autocomplete="off">
                </div>
                <div class="cmd-body" id="cmdBody">
                    <!-- Quick actions -->
                    <div class="cmd-section-label">⚡ Szybkie akcje</div>
                    <div class="cmd-item" data-search="nowe zadanie dodaj" onclick="window.location.href='/pages/tasks.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-plus"></i></div>
                        <div><div class="cmd-item-text">Nowe zadanie</div><div class="cmd-item-sub">Stwórz nowe zadanie w projekcie</div></div>
                        <span class="cmd-item-kbd">N</span>
                    </div>
                    <div class="cmd-item" data-search="nowy projekt stwórz" onclick="window.location.href='/pages/projects.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-folder-plus"></i></div>
                        <div><div class="cmd-item-text">Nowy projekt</div><div class="cmd-item-sub">Stwórz projekt zespołowy</div></div>
                    </div>
                    <div class="cmd-item" data-search="dodaj użytkownika team" onclick="window.location.href='/pages/team.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-user-plus"></i></div>
                        <div><div class="cmd-item-text">Dodaj użytkownika</div><div class="cmd-item-sub">Zarządzaj zespołem</div></div>
                    </div>
                    <div class="cmd-item" data-search="notatka stwórz notatkę" onclick="window.location.href='/pages/notes.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-note-sticky"></i></div>
                        <div><div class="cmd-item-text">Nowa notatka</div><div class="cmd-item-sub">Stwórz prywatną notatkę</div></div>
                    </div>

                    <!-- Navigation -->
                    <div class="cmd-section-label">🧭 Nawigacja</div>
                    <div class="cmd-item" data-search="dashboard panel główny" onclick="window.location.href='/pages/dashboard.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-chart-line"></i></div>
                        <div class="cmd-item-text">Dashboard</div>
                    </div>
                    <div class="cmd-item" data-search="projekty folder" onclick="window.location.href='/pages/projects.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-folder-open"></i></div>
                        <div class="cmd-item-text">Projekty</div>
                    </div>
                    <div class="cmd-item" data-search="zadania lista" onclick="window.location.href='/pages/tasks.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-list-check"></i></div>
                        <div class="cmd-item-text">Zadania</div>
                    </div>
                    <div class="cmd-item" data-search="kalendarz calendar" onclick="window.location.href='/pages/calendar.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-calendar-days"></i></div>
                        <div class="cmd-item-text">Kalendarz</div>
                    </div>
                    <div class="cmd-item" data-search="zespół team użytkownicy" onclick="window.location.href='/pages/team.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="cmd-item-text">Zespół</div>
                    </div>
                    <div class="cmd-item" data-search="raporty statystyki wykresy" onclick="window.location.href='/pages/reports.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-chart-bar"></i></div>
                        <div class="cmd-item-text">Raporty</div>
                    </div>
                    <div class="cmd-item" data-search="ustawienia settings" onclick="window.location.href='/pages/settings.php'">
                        <div class="cmd-item-icon"><i class="fa-solid fa-sliders"></i></div>
                        <div class="cmd-item-text">Ustawienia</div>
                    </div>

                    <!-- Theme -->
                    <div class="cmd-section-label">🎨 Motyw</div>
                    <div class="cmd-item" data-search="dark ciemny motyw" onclick="setThemeDark();closeCommandPalette()">
                        <div class="cmd-item-icon"><i class="fa-solid fa-moon"></i></div>
                        <div class="cmd-item-text">Ciemny motyw</div>
                    </div>
                    <div class="cmd-item" data-search="light jasny motyw" onclick="setThemeLight();closeCommandPalette()">
                        <div class="cmd-item-icon"><i class="fa-solid fa-sun"></i></div>
                        <div class="cmd-item-text">Jasny motyw</div>
                    </div>
                </div>
                <div class="cmd-footer">
                    <span><kbd>↑</kbd><kbd>↓</kbd> Nawigacja</span>
                    <span><kbd>Enter</kbd> Wybierz</span>
                    <span><kbd>Esc</kbd> Zamknij</span>
                </div>
            </div>
            </div>

            <!-- Content Area -->
            <main class="content-body">
