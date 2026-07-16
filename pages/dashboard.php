<?php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Stats
$stmt_projects = $db->prepare("SELECT COUNT(DISTINCT p.id) FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0");
$stmt_projects->execute([$user_id, $user_id]);
$projects_count = (int)$stmt_projects->fetchColumn();

$stmt_tasks = $db->prepare("SELECT COUNT(*) FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND t.status != 'Done' AND p.is_archived = 0");
$stmt_tasks->execute([$user_id, $user_id]);
$active_tasks_count = (int)$stmt_tasks->fetchColumn();

$stmt_today = $db->prepare("SELECT t.*, p.name as project_name, p.color FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND t.deadline = CURDATE() AND t.status != 'Done' AND p.is_archived = 0");
$stmt_today->execute([$user_id, $user_id]);
$tasks_today = $stmt_today->fetchAll();

$stmt_overdue = $db->prepare("SELECT COUNT(*) FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND t.deadline < CURDATE() AND t.status != 'Done' AND p.is_archived = 0");
$stmt_overdue->execute([$user_id, $user_id]);
$overdue_count = (int)$stmt_overdue->fetchColumn();

$stmt_done = $db->prepare("SELECT COUNT(*) FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND t.status = 'Done' AND p.is_archived = 0");
$stmt_done->execute([$user_id, $user_id]);
$done_count = (int)$stmt_done->fetchColumn();

$stmt_pri = $db->prepare("SELECT t.priority, COUNT(*) as qty FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0 GROUP BY t.priority");
$stmt_pri->execute([$user_id, $user_id]);
$priorities_data = $stmt_pri->fetchAll();
$priorities_json = ['Low' => 0, 'Medium' => 0, 'High' => 0, 'Critical' => 0];
foreach ($priorities_data as $pd) if (isset($priorities_json[$pd['priority']])) $priorities_json[$pd['priority']] = (int)$pd['qty'];

$stmt_logs = $db->prepare("SELECT l.*, u.full_name FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 10");
$stmt_logs->execute();
$activity_logs = $stmt_logs->fetchAll();

$stmt_top_proj = $db->prepare("
    SELECT DISTINCT p.id, p.name, p.color,
        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total,
        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status='Done') as done,
        (SELECT COUNT(DISTINCT user_id) FROM project_members WHERE project_id = p.id) as member_count
    FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
    LIMIT 5
");
$stmt_top_proj->execute([$user_id, $user_id]);
$top_projects = $stmt_top_proj->fetchAll();

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Dzień dobry' : ($hour < 18 ? 'Cześć' : 'Dobry wieczór');
?>

<link rel="stylesheet" href="/assets/css/dashboard.css">

<!-- ═══ PREMIUM HERO ═══════════════════════════════════════════════════════════ -->
<div class="premium-hero">
    <div class="hero-left">
        <div class="hero-greeting"><?= $greeting ?>, 👋</div>
        <div class="hero-name"><?= sanitize($user_name) ?>!</div>
        <div class="hero-stats">
            <span class="hero-stat"><i class="fa-solid fa-list-check"></i> <?= count($tasks_today) ?> zadań na dziś</span>
            <?php if ($overdue_count > 0): ?>
            <span class="hero-stat" style="background:rgba(239,68,68,.25)"><i class="fa-solid fa-clock"></i> <?= $overdue_count ?> po terminie</span>
            <?php endif; ?>
            <span class="hero-stat"><i class="fa-solid fa-circle-check"></i> <?= $done_count ?> ukończonych</span>
        </div>
        <div class="hero-actions">
            <button class="hero-btn hero-btn-primary" onclick="window.location.href='/pages/tasks.php'">
                <i class="fa-solid fa-plus"></i> Nowe zadanie
            </button>
            <button class="hero-btn hero-btn-ghost" onclick="window.location.href='/pages/projects.php'">
                <i class="fa-solid fa-folder-plus"></i> Nowy projekt
            </button>
            <button class="hero-btn hero-btn-ghost" onclick="openCommandPalette()">
                <i class="fa-solid fa-terminal"></i> Komendy
                <kbd style="background:rgba(255,255,255,.15);border-radius:4px;padding:1px 5px;font-size:10px;border:1px solid rgba(255,255,255,.2)">Ctrl+K</kbd>
            </button>
        </div>
    </div>

    <!-- Workspace card -->
    <div class="hero-workspace-card">
        <div class="hero-ws-label">Workspace</div>
        <div class="hero-ws-name"><?= sanitize($user_name) ?>'s Team</div>
        <div class="hero-ws-row"><span><i class="fa-solid fa-users"></i> Członkowie</span><strong><?= $ws_members ?></strong></div>
        <div class="hero-ws-row"><span><i class="fa-solid fa-folder"></i> Projekty</span><strong><?= $ws_projects ?></strong></div>
        <?php
            $all_tasks_total = (int)$db->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
            $all_tasks_done  = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status='Done'")->fetchColumn();
            $ws_pct = $all_tasks_total > 0 ? round($all_tasks_done / $all_tasks_total * 100) : 0;
        ?>
        <div class="hero-ws-row"><span><i class="fa-solid fa-chart-pie"></i> Ukończenie</span><strong><?= $ws_pct ?>%</strong></div>
        <div class="hero-ws-bar"><div class="hero-ws-fill" style="width:<?= $ws_pct ?>%"></div></div>
    </div>
</div>

<!-- ═══ PREMIUM KPI CARDS ════════════════════════════════════════════════════════ -->
<div class="pkpi-grid">

    <div class="pkpi-card" style="--pkpi-color:#6366f1;--pkpi-light:rgba(99,102,241,.1);--pkpi-grad:linear-gradient(90deg,#6366f1,#8b5cf6);--pkpi-glow:rgba(99,102,241,.1)"
         onclick="window.location.href='/pages/projects.php'">
        <div class="pkpi-top">
            <div class="pkpi-icon"><i class="fa-solid fa-folder-open"></i></div>
            <span class="pkpi-trend flat"><i class="fa-solid fa-minus"></i> aktywne</span>
        </div>
        <div class="pkpi-value" data-counter="<?= $projects_count ?>"><?= $projects_count ?></div>
        <div class="pkpi-label">Projekty</div>
        <div class="pkpi-sub">Kliknij aby zobaczyć wszystkie →</div>
    </div>

    <div class="pkpi-card" style="--pkpi-color:#06b6d4;--pkpi-light:rgba(6,182,212,.1);--pkpi-grad:linear-gradient(90deg,#06b6d4,#0ea5e9);--pkpi-glow:rgba(6,182,212,.1)"
         onclick="window.location.href='/pages/tasks.php'">
        <div class="pkpi-top">
            <div class="pkpi-icon"><i class="fa-solid fa-list-check"></i></div>
            <?php if ($active_tasks_count > 0): ?>
            <span class="pkpi-trend up"><i class="fa-solid fa-arrow-up"></i> w toku</span>
            <?php else: ?>
            <span class="pkpi-trend flat"><i class="fa-solid fa-check"></i> brak</span>
            <?php endif; ?>
        </div>
        <div class="pkpi-value" data-counter="<?= $active_tasks_count ?>"><?= $active_tasks_count ?></div>
        <div class="pkpi-label">Aktywne zadania</div>
        <div class="pkpi-sub"><?= $done_count ?> ukończonych łącznie</div>
    </div>

    <div class="pkpi-card" style="--pkpi-color:#22c55e;--pkpi-light:rgba(34,197,94,.1);--pkpi-grad:linear-gradient(90deg,#22c55e,#10b981);--pkpi-glow:rgba(34,197,94,.1)">
        <div class="pkpi-top">
            <div class="pkpi-icon"><i class="fa-solid fa-circle-check"></i></div>
            <span class="pkpi-trend up"><i class="fa-solid fa-arrow-up"></i> ukończone</span>
        </div>
        <div class="pkpi-value" data-counter="<?= $done_count ?>"><?= $done_count ?></div>
        <div class="pkpi-label">Ukończone</div>
        <div class="pkpi-sub"><?= $ws_pct ?>% wszystkich zadań</div>
    </div>

    <div class="pkpi-card" style="--pkpi-color:<?= $overdue_count > 0 ? '#ef4444' : '#10b981' ?>;--pkpi-light:<?= $overdue_count > 0 ? 'rgba(239,68,68,.1)' : 'rgba(16,185,129,.1)' ?>;--pkpi-grad:<?= $overdue_count > 0 ? 'linear-gradient(90deg,#ef4444,#dc2626)' : 'linear-gradient(90deg,#22c55e,#10b981)' ?>;--pkpi-glow:rgba(239,68,68,.08)">
        <div class="pkpi-top">
            <div class="pkpi-icon"><i class="fa-solid fa-clock"></i></div>
            <?php if ($overdue_count > 0): ?>
            <span class="pkpi-trend down"><i class="fa-solid fa-triangle-exclamation"></i> pilne</span>
            <?php else: ?>
            <span class="pkpi-trend up"><i class="fa-solid fa-check"></i> ok</span>
            <?php endif; ?>
        </div>
        <div class="pkpi-value" data-counter="<?= $overdue_count ?>"><?= $overdue_count ?></div>
        <div class="pkpi-label">Po terminie</div>
        <div class="pkpi-sub"><?= count($tasks_today) ?> zadań na dziś</div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/components/dashboard_tasks.php'; ?>

<?php require_once __DIR__ . '/../includes/components/dashboard_projects.php'; ?>

<!-- Productivity Trend -->
<div class="report-section">
    <div class="section-header">
        <div class="report-title" style="margin:0"><i class="fa-solid fa-chart-line"></i> Trend Produktywności (7 dni)</div>
        <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--success)">
            <span style="width:8px;height:8px;border-radius:50%;background:var(--success);animation:pulse 2s infinite;display:inline-block"></span>
            Live
        </span>
    </div>
    <canvas id="trendChart" style="max-height:280px"></canvas>
</div>


<script>
window.dashboardData = {
    priorities: [<?= implode(',', array_values($priorities_json)) ?>]
};
</script>
<script src="/assets/js/dashboard.js"></script>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
