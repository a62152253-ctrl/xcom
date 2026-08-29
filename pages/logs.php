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


<!-- Hero -->
<div class="logs-hero animate-fade">
    <h1><i class="fa-solid fa-history"></i> Logi Aktywności</h1>
    <p>Kompletna historia Twoich działań w workspace</p>
</div>

<!-- Main Content -->
<div class="logs-container">
    <!-- Sidebar Filters -->
    <aside class="logs-sidebar">
        <h3><i class="fa-solid fa-sliders"></i> Filtry</h3>

        <!-- Date Filter -->
        <div class="filter-group">
            <label class="filter-label">Data</label>
            <input type="date" class="filter-control" value="<?= $filter_date ?>" onchange="applyFilter('date', this.value)">
        </div>

        <!-- Action Quick Filters -->
        <div class="filter-group">
            <label class="filter-label">Typ Akcji</label>
            <button class="filter-btn <?= !$filter_action ? 'active' : '' ?>" onclick="applyFilter('action', '')">
                <i class="fa-solid fa-list"></i> Wszystkie
            </button>
            <button class="filter-btn <?= $filter_action === 'login' ? 'active' : '' ?>" onclick="applyFilter('action', 'login')">
                <i class="fa-solid fa-right-to-bracket"></i> Logowanie
            </button>
            <button class="filter-btn <?= $filter_action === 'task' ? 'active' : '' ?>" onclick="applyFilter('action', 'task')">
                <i class="fa-solid fa-list-check"></i> Zadania
            </button>
            <button class="filter-btn <?= $filter_action === 'project' ? 'active' : '' ?>" onclick="applyFilter('action', 'project')">
                <i class="fa-solid fa-folder"></i> Projekty
            </button>
            <button class="filter-btn <?= $filter_action === 'user' ? 'active' : '' ?>" onclick="applyFilter('action', 'user')">
                <i class="fa-solid fa-user"></i> Użytkownicy
            </button>
        </div>

        <!-- Clear Filters -->
        <button class="filter-btn" onclick="window.location.href='/pages/logs.php'" style="margin-top: 1rem; border-color: var(--border-color); color: var(--text-secondary);">
            <i class="fa-solid fa-redo"></i> Resetuj
        </button>
    </aside>

    <!-- Main Logs -->
    <div class="logs-main">
        <?php if (empty($logs)): ?>
        <div class="empty-state-logs">
            <i class="fa-regular fa-inbox"></i>
            <p>Brak logów do wyświetlenia</p>
        </div>
        <?php else: ?>
        <div class="logs-timeline">
            <?php foreach ($logs as $log):
                $icon_data = get_action_icon_color($log['action']);
            ?>
            <div class="log-entry">
                <div class="log-card">
                    <div class="log-header">
                        <div class="log-icon" style="background: <?= $icon_data['bg'] ?>; color: <?= $icon_data['color'] ?>;">
                            <i class="fa-solid <?= $icon_data['icon'] ?>"></i>
                        </div>
                        <div class="log-info">
                            <div class="log-user"><?= sanitize($log['full_name'] ?? 'System') ?></div>
                            <?php if (!empty($log['email'])): ?>
                            <div class="log-email"><?= sanitize($log['email']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="log-time">
                            <i class="fa-regular fa-clock"></i>
                            <?= date('d.m.Y H:i', strtotime($log['created_at'])) ?>
                        </div>
                    </div>
                    <div class="log-action">
                        <span class="log-action-code"><?= str_replace('_', ' ', sanitize($log['action'])) ?></span>
                        <?php if (!empty($log['description'])): ?>
                        <div style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--text-secondary); opacity: 0.8;">
                            <?= sanitize($log['description']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <button class="pagination-btn" onclick="goToPage(<?= max(1, $page - 1) ?>)" <?= $page <= 1 ? 'disabled' : '' ?>>
                <i class="fa-solid fa-chevron-left"></i> Wstecz
            </button>
            <span class="pagination-info">
                <?= $page ?> / <?= $total_pages ?> • <?= $total ?> logów
            </span>
            <button class="pagination-btn" onclick="goToPage(<?= min($total_pages, $page + 1) ?>)" <?= $page >= $total_pages ? 'disabled' : '' ?>>
                Dalej <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function applyFilter(type, value) {
    const params = new URLSearchParams(window.location.search);
    if (type === 'date') params.set('date', value);
    else if (type === 'action') params.set('action', value);
    params.set('page', '1');
    window.location.href = '/pages/logs.php?' + params.toString();
}

function goToPage(page) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', page);
    window.location.href = '/pages/logs.php?' + params.toString();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
