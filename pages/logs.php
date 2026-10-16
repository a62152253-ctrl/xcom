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

<style>
.logs-hero {
    background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
    color: white;
    padding: 3rem 2rem;
    border-radius: 16px;
    margin-bottom: 2.5rem;
    box-shadow: 0 20px 40px rgba(59, 130, 246, 0.25);
    position: relative;
    overflow: hidden;
}

.logs-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
}

.logs-hero h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2.25rem;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.logs-hero p {
    margin: 0;
    opacity: 0.95;
    font-size: 1.05rem;
    position: relative;
    z-index: 1;
}

.logs-container {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 2rem;
}

.logs-sidebar {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 1.75rem;
    height: fit-content;
    box-shadow: var(--shadow-sm);
    position: sticky;
    top: 2rem;
}

.logs-sidebar h3 {
    margin: 0 0 1.5rem 0;
    font-size: 1.05rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--text-primary);
}

.filter-group {
    margin-bottom: 1.75rem;
}

.filter-group:last-of-type {
    margin-bottom: 0;
}

.filter-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.75rem;
    display: block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-secondary);
    color: var(--text-primary);
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.filter-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    background: var(--bg-primary);
}

.filter-btn {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1.5px solid var(--border-color);
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    margin-bottom: 0.6rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.filter-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: rgba(59, 130, 246, 0.05);
    transform: translateX(2px);
}

.filter-btn.active {
    background: linear-gradient(135deg, var(--primary), #1d4ed8);
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.logs-main {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 2.5rem;
    box-shadow: var(--shadow-sm);
}

.logs-timeline {
    position: relative;
    padding-left: 2.5rem;
}

.logs-timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, var(--primary), var(--primary) 40%, transparent);
    border-radius: 2px;
}

.log-entry {
    position: relative;
    padding-bottom: 2.5rem;
    animation: slideInLeft 0.4s ease forwards;
    opacity: 0;
}

.log-entry:nth-child(1) { animation-delay: 0.05s; }
.log-entry:nth-child(2) { animation-delay: 0.1s; }
.log-entry:nth-child(3) { animation-delay: 0.15s; }
.log-entry:nth-child(n+4) { animation-delay: 0.2s; }

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.log-entry::before {
    content: '';
    position: absolute;
    left: -2.75rem;
    top: 0.6rem;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--bg-secondary);
    border: 3px solid var(--primary);
    box-shadow: 0 0 0 4px var(--bg-primary);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.log-entry:hover::before {
    border-color: var(--primary);
    box-shadow: 0 0 0 8px rgba(59, 130, 246, 0.15);
    transform: scale(1.3);
}

.log-card {
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.log-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
    transition: left 0.5s ease;
}

.log-card:hover {
    border-color: var(--primary);
    box-shadow: 0 12px 32px rgba(59, 130, 246, 0.15);
    transform: translateY(-4px);
}

.log-card:hover::before {
    left: 100%;
}

.log-header {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
}

.log-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.25rem;
    transition: all 0.2s ease;
}

.log-card:hover .log-icon {
    transform: scale(1.1) rotate(5deg);
}

.log-info {
    flex: 1;
    min-width: 0;
}

.log-user {
    font-weight: 700;
    color: var(--text-primary);
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
}

.log-email {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.log-time {
    font-size: 0.8rem;
    color: var(--text-muted);
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.log-action {
    font-size: 0.9rem;
    color: var(--text-secondary);
    padding: 1rem;
    background: var(--bg-tertiary);
    border-radius: 8px;
    word-break: break-word;
    border-left: 4px solid var(--primary);
    position: relative;
    z-index: 1;
}

.log-action-code {
    font-family: 'Monaco', 'Courier New', monospace;
    font-size: 0.85rem;
    color: var(--primary);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
    margin-top: 2.5rem;
    align-items: center;
    flex-wrap: wrap;
}

.pagination-btn {
    padding: 0.75rem 1.25rem;
    border: 1.5px solid var(--border-color);
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.25s ease;
}

.pagination-btn:hover:not(:disabled) {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    transform: translateY(-2px);
}

.pagination-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pagination-info {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin: 0 1rem;
    font-weight: 600;
}

.empty-state-logs {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}

.empty-state-logs i {
    font-size: 3.5rem;
    opacity: 0.2;
    margin-bottom: 1rem;
    display: block;
}

.empty-state-logs p {
    font-size: 1rem;
    margin: 0;
}

@media (max-width: 1024px) {
    .logs-container {
        grid-template-columns: 1fr;
    }

    .logs-sidebar {
        position: static;
        top: auto;
    }

    .logs-main {
        padding: 1.75rem;
    }
}

@media (max-width: 640px) {
    .logs-hero {
        padding: 2rem 1.5rem;
    }

    .logs-hero h1 {
        font-size: 1.75rem;
    }

    .logs-sidebar {
        padding: 1.25rem;
    }

    .logs-main {
        padding: 1.25rem;
    }

    .log-card {
        padding: 1rem;
    }

    .log-header {
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .log-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .log-time {
        order: 3;
        width: 100%;
        margin-top: 0.5rem;
    }
}
</style>

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
