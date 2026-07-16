<?php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Get activity logs with advanced filtering
$page = (int)($_GET['page'] ?? 1);
$per_page = 50;
$offset = ($page - 1) * $per_page;

$filter_action = trim($_GET['action'] ?? '');
$filter_date = trim($_GET['date'] ?? '');

$where = "WHERE l.user_id = ?";
$params = [$user_id];

if ($filter_action) {
    $where .= " AND l.action LIKE ?";
    $params[] = "%$filter_action%";
}

if ($filter_date) {
    $where .= " AND DATE(l.created_at) = ?";
    $params[] = $filter_date;
}

// Get total count
$stmt_count = $db->prepare("SELECT COUNT(*) FROM activity_logs l $where");
$stmt_count->execute($params);
$total = (int)$stmt_count->fetchColumn();
$total_pages = ceil($total / $per_page);

// Get logs
$stmt_logs = $db->prepare("
    SELECT l.*, u.full_name, u.email, u.avatar
    FROM activity_logs l
    LEFT JOIN users u ON l.user_id = u.id
    $where
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt_logs->execute(array_merge($params, [$per_page, $offset]));
$logs = $stmt_logs->fetchAll();

// Action icons and colors
$action_icons = [
    'user' => ['icon' => 'fa-user', 'color' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.1)'],
    'project' => ['icon' => 'fa-folder', 'color' => '#8b5cf6', 'bg' => 'rgba(139, 92, 246, 0.1)'],
    'task' => ['icon' => 'fa-list-check', 'color' => '#06b6d4', 'bg' => 'rgba(6, 182, 212, 0.1)'],
    'note' => ['icon' => 'fa-note-sticky', 'color' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.1)'],
    'login' => ['icon' => 'fa-right-to-bracket', 'color' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.1)'],
    'logout' => ['icon' => 'fa-right-from-bracket', 'color' => '#ef4444', 'bg' => 'rgba(239, 68, 68, 0.1)'],
    'notification' => ['icon' => 'fa-bell', 'color' => '#ec4899', 'bg' => 'rgba(236, 72, 153, 0.1)'],
];

function get_action_icon_color($action) {
    global $action_icons;
    foreach ($action_icons as $key => $val) {
        if (strpos($action, $key) !== false) {
            return $val;
        }
    }
    return ['icon' => 'fa-activity', 'color' => '#6b7280', 'bg' => 'rgba(107, 114, 128, 0.1)'];
}
?>

<link rel="stylesheet" href="/assets/css/logs.css">

<!-- Hero -->
<div class="logs-hero animate-fade">
    <h1><i class="fa-solid fa-history"></i> Logi Aktywności</h1>
    <p>Kompletna historia Twoich działań w workspace</p>
</div>

<!-- Main Content -->
<div class="logs-container">
    <?php require_once __DIR__ . '/../includes/components/logs_sidebar.php'; ?>

    <?php require_once __DIR__ . '/../includes/components/logs_main.php'; ?>
</div>

<script src="/assets/js/logs.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
