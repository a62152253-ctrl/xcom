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

// Get logs - ALL workspace logs, no user filter
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
    'user' => ['icon' => 'fa-user', 'color' => '#3b82f6'],
    'project' => ['icon' => 'fa-folder', 'color' => '#8b5cf6'],
    'task' => ['icon' => 'fa-list-check', 'color' => '#06b6d4'],
    'note' => ['icon' => 'fa-note-sticky', 'color' => '#f59e0b'],
    'login' => ['icon' => 'fa-right-to-bracket', 'color' => '#10b981'],
    'logout' => ['icon' => 'fa-right-from-bracket', 'color' => '#ef4444'],
    'notification' => ['icon' => 'fa-bell', 'color' => '#ec4899'],
];

function get_action_icon_color($action) {
    global $action_icons;
    foreach ($action_icons as $key => $val) {
        if (strpos($action, $key) !== false) {
            return $val;
        }
    }
    return ['icon' => 'fa-activity', 'color' => '#6b7280'];
}
?>

<style>
.logs-hero {
    background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
    color: white;
    padding: 2.5rem 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.2);
}

.logs-hero h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
}

.logs-hero p {
    margin: 0;
    opacity: 0.95;
}

.logs-container {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
}

.logs-sidebar {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    height: fit-content;
    box-shadow: var(--shadow-sm);
}

.logs-sidebar h3 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 700;
}

.filter-group {
    margin-bottom: 1.5rem;
}

.filter-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    display: block;
}

.filter-control {
    width: 100%;
    padding: 0.6rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-secondary);
    color: var(--text-primary);
    font-size: 0.9rem;
}

.filter-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-btn {
    width: 100%;
    padding: 0.6rem;
    border: 1px solid var(--border-color);
    background: transparent;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s ease;
    margin-bottom: 0.5rem;
}

.filter-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.filter-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.logs-main {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: var(--shadow-sm);
}

.logs-timeline {
    position: relative;
    padding-left: 2rem;
}

.logs-timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, var(--primary), transparent);
}

.log-entry {
    position: relative;
    padding-bottom: 2rem;
}

.log-entry::before {
    content: '';
    position: absolute;
    left: -2.25rem;
    top: 0.5rem;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--bg-secondary);
    border: 3px solid var(--border-color);
    transition: all 0.2s ease;
}

.log-entry:hover::before {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.log-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 1.25rem;
    transition: all 0.2s ease;
    cursor: pointer;
}

.log-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
    transform: translateX(4px);
}

.log-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.log-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.1rem;
}

.log-info {
    flex: 1;
}

.log-user {
    font-weight: 700;
    color: var(--text-primary);
    font-size: 0.95rem;
}

.log-email {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.log-time {
    font-size: 0.8rem;
    color: var(--text-muted);
    white-space: nowrap;
}

.log-action {
    font-size: 0.9rem;
    color: var(--text-secondary);
    padding: 0.75rem;
    background: var(--bg-tertiary);
    border-radius: 6px;
    word-break: break-word;
}

.log-action-code {
    font-family: 'Monaco', 'Courier New', monospace;
    font-size: 0.85rem;
    color: var(--primary);
    font-weight: 600;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
    align-items: center;
}

.pagination-btn {
    padding: 0.6rem 1rem;
    border: 1px solid var(--border-color);
    background: var(--bg-secondary);
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled) {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-info {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin: 0 1rem;
}

.empty-state-logs {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-muted);
}

.empty-state-logs i {
    font-size: 3rem;
    opacity: 0.3;
    margin-bottom: 1rem;
}

@media (max-width: 1024px) {
    .logs-container {
        grid-template-columns: 1fr;
    }

    .logs-sidebar {
        height: auto;
    }
}

@media (max-width: 640px) {
    .logs-hero {
        padding: 1.5rem 1rem;
    }

    .logs-hero h1 {
        font-size: 1.5rem;
    }

    .logs-main {
        padding: 1rem;
    }

    .log-card {
        padding: 1rem;
    }

    .log-header {
        flex-wrap: wrap;
    }
}
</style>

<!-- Hero -->
<div class="logs-hero animate-fade">
    <h1><i class="fa-solid fa-history"></i> Logi Aktywności</h1>
    <p>Pełna historia działań w workspace — kto, co, kiedy.</p>
</div>

<!-- Main Content -->
<div class="logs-container">
    <!-- Sidebar Filters -->
    <aside class="logs-sidebar">
        <h3><i class="fa-solid fa-filter"></i> Filtry</h3>

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
        <button class="filter-btn" onclick="window.location.href='/pages/logs.php'" style="margin-top: 1rem;">
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
                        <div class="log-icon" style="background: <?= $icon_data['color'] ?>20; color: <?= $icon_data['color'] ?>;">
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
                        <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-secondary);">
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
                Strona <?= $page ?> z <?= $total_pages ?> (<?= $total ?> logów)
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
