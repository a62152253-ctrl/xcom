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
        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status='Done') as done
    FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
    LIMIT 5
");
$stmt_top_proj->execute([$user_id, $user_id]);
$top_projects = $stmt_top_proj->fetchAll();

$hour = (int)date('H');
$greeting = $hour < 12 ? 'DzieĹ„ dobry' : ($hour < 18 ? 'CzeĹ›Ä‡' : 'Dobry wieczĂłr');
?>

<style>
.dashboard-hero {
    background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
    color: white;
    padding: 3rem 2rem;
    border-radius: 16px;
    margin-bottom: 2.5rem;
    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.2);
}

.dashboard-hero h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
}

.dashboard-hero p {
    margin: 0;
    opacity: 0.95;
    font-size: 1.05rem;
}

.dashboard-hero .welcome-name {
    font-weight: 700;
    background: rgba(255,255,255,0.25);
    padding: 0.25rem 0.75rem;
    border-radius: 8px;
}

.dashboard-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), #06b6d4);
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    border-color: var(--primary);
}

.kpi-card-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.kpi-card-info h3 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary);
}

.kpi-card-info p {
    margin: 0.5rem 0 0 0;
    color: var(--text-secondary);
    font-size: 0.95rem;
}

.kpi-card-icon {
    font-size: 3rem;
    opacity: 0.15;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--text-primary);
}

.tasks-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.task-card {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
}

.task-card h3 {
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
}

.task-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 0.75rem;
    border-left: 4px solid transparent;
    transition: all 0.2s ease;
    cursor: pointer;
}

.task-item:hover {
    background: var(--bg-secondary);
    border-left-color: var(--primary);
}

.task-item-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.task-item-info {
    flex: 1;
}

.task-item-name {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.task-item-project {
    font-size: 0.85rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.chart-container {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
}

.projects-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.project-list {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
}

.project-item {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
    margin-bottom: 0.75rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.project-item:hover {
    background: var(--bg-secondary);
}

.project-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.project-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.project-percent {
    font-weight: 700;
    color: var(--primary);
}

.progress-compact {
    height: 6px;
    background: var(--bg-tertiary);
    border-radius: 3px;
    overflow: hidden;
}

.progress-compact-bar {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), #60a5fa);
    transition: width 0.4s ease;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 3rem;
    opacity: 0.3;
    margin-bottom: 0.75rem;
}

@media (max-width: 768px) {
    .dashboard-hero {
        padding: 2rem 1.5rem;
    }

    .dashboard-hero h1 {
        font-size: 1.5rem;
    }

    .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .tasks-grid,
    .projects-row {
        grid-template-columns: 1fr;
    }

    .kpi-card {
        padding: 1rem;
    }

    .kpi-card-info h3 {
        font-size: 2rem;
    }
}
</style>

<!-- â•â•â• PREMIUM HERO â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="premium-hero">
    <div class="hero-left">
        <div class="hero-greeting"><?= $greeting ?>, đź‘‹</div>
        <div class="hero-name"><?= sanitize($user_name) ?>!</div>
        <div class="hero-stats">
            <span class="hero-stat"><i class="fa-solid fa-list-check"></i> <?= count($tasks_today) ?> zadaĹ„ na dziĹ›</span>
            <?php if ($overdue_count > 0): ?>
            <span class="hero-stat" style="background:rgba(239,68,68,.25)"><i class="fa-solid fa-clock"></i> <?= $overdue_count ?> po terminie</span>
            <?php endif; ?>
            <span class="hero-stat"><i class="fa-solid fa-circle-check"></i> <?= $done_count ?> ukoĹ„czonych</span>
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
        <div class="hero-ws-row"><span><i class="fa-solid fa-users"></i> CzĹ‚onkowie</span><strong><?= $ws_members ?></strong></div>
        <div class="hero-ws-row"><span><i class="fa-solid fa-folder"></i> Projekty</span><strong><?= $ws_projects ?></strong></div>
        <?php
            $all_tasks_total = (int)$db->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
            $all_tasks_done  = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status='Done'")->fetchColumn();
            $ws_pct = $all_tasks_total > 0 ? round($all_tasks_done / $all_tasks_total * 100) : 0;
        ?>
        <div class="hero-ws-row"><span><i class="fa-solid fa-chart-pie"></i> UkoĹ„czenie</span><strong><?= $ws_pct ?>%</strong></div>
        <div class="hero-ws-bar"><div class="hero-ws-fill" style="width:<?= $ws_pct ?>%"></div></div>
    </div>
</div>

<!-- â•â•â• PREMIUM KPI CARDS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="pkpi-grid">

    <div class="pkpi-card" style="--pkpi-color:#6366f1;--pkpi-light:rgba(99,102,241,.1);--pkpi-grad:linear-gradient(90deg,#6366f1,#8b5cf6);--pkpi-glow:rgba(99,102,241,.1)"
         onclick="window.location.href='/pages/projects.php'">
        <div class="pkpi-top">
            <div class="pkpi-icon"><i class="fa-solid fa-folder-open"></i></div>
            <span class="pkpi-trend flat"><i class="fa-solid fa-minus"></i> aktywne</span>
        </div>
        <div class="pkpi-value" data-counter="<?= $projects_count ?>"><?= $projects_count ?></div>
        <div class="pkpi-label">Projekty</div>
        <div class="pkpi-sub">Kliknij aby zobaczyÄ‡ wszystkie â†’</div>
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
        <div class="pkpi-sub"><?= $done_count ?> ukoĹ„czonych Ĺ‚Ä…cznie</div>
    </div>

    <div class="pkpi-card" style="--pkpi-color:#22c55e;--pkpi-light:rgba(34,197,94,.1);--pkpi-grad:linear-gradient(90deg,#22c55e,#10b981);--pkpi-glow:rgba(34,197,94,.1)">
        <div class="pkpi-top">
            <div class="pkpi-icon"><i class="fa-solid fa-circle-check"></i></div>
            <span class="pkpi-trend up"><i class="fa-solid fa-arrow-up"></i> ukoĹ„czone</span>
        </div>
        <div class="pkpi-value" data-counter="<?= $done_count ?>"><?= $done_count ?></div>
        <div class="pkpi-label">UkoĹ„czone</div>
        <div class="pkpi-sub"><?= $ws_pct ?>% wszystkich zadaĹ„</div>
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
        <div class="pkpi-sub"><?= count($tasks_today) ?> zadaĹ„ na dziĹ›</div>
    </div>

</div>

<!-- â•â•â• MAIN GRID â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="tasks-grid">

    <!-- Today's tasks -->
    <div class="task-card">
        <div class="section-header">
            <h3 class="section-title-premium">
                <i class="fa-solid fa-calendar-check" style="color:var(--primary)"></i>
                Zadania na dziĹ›
                <span class="section-badge"><?= count($tasks_today) ?></span>
            </h3>
            <a href="/pages/tasks.php" style="font-size:12px;color:var(--primary);font-weight:600;text-decoration:none">
                Wszystkie â†’
            </a>
        </div>

        <?php if (count($tasks_today) > 0): ?>
            <?php foreach ($tasks_today as $t): ?>
            <div class="task-premium" onclick="window.location.href='/pages/tasks.php?task_id=<?= $t['id'] ?>'">
                <div class="task-check"><i class="fa-regular fa-circle"></i></div>
                <div class="task-premium-body">
                    <div class="task-premium-name"><?= sanitize($t['name']) ?></div>
                    <div class="task-premium-meta">
                        <span style="width:8px;height:8px;border-radius:50%;background:<?= $t['color'] ?>;display:inline-block"></span>
                        <?= sanitize($t['project_name']) ?>
                        <span class="task-prio prio-<?= strtolower($t['priority']) ?>"><?= $t['priority'] ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state-premium">
                <div class="es-icon">â€ď¸Ź</div>
                <div class="es-title">Brak zadaĹ„ na dziĹ›!</div>
                <div class="es-sub">Masz wolny dzieĹ„ albo juĹĽ wszystko ukoĹ„czone. Ĺšwietna robota!</div>
                <a href="/pages/tasks.php" class="es-btn"><i class="fa-solid fa-plus"></i> UtwĂłrz zadanie</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Priority Chart -->
    <div class="chart-container">
        <h3><i class="fa-solid fa-chart-pie"></i> RozkĹ‚ad PriorytetĂłw</h3>
        <canvas id="priorityChart" style="max-height:250px"></canvas>
    </div>
</div>

<!-- â•â•â• PROJECTS & ACTIVITY â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="projects-row">

    <!-- Projects Progress -->
    <div class="project-list">
        <div class="section-header">
            <h3 class="section-title-premium"><i class="fa-solid fa-chart-line" style="color:var(--primary)"></i> Projekty</h3>
            <a href="/pages/projects.php" style="font-size:12px;color:var(--primary);font-weight:600;text-decoration:none">Wszystkie â†’</a>
        </div>

        <?php if (!empty($top_projects)):
            foreach ($top_projects as $proj):
                $pct = $proj['total'] > 0 ? round($proj['done']/$proj['total']*100) : 0;
        ?>
        <div class="proj-card-premium" style="--proj-color:<?= $proj['color'] ?>"
             onclick="window.location.href='/pages/tasks.php?project_id=<?= $proj['id'] ?>'">
            <div class="proj-card-top">
                <div class="proj-color-dot" style="background:<?= $proj['color'] ?>22;color:<?= $proj['color'] ?>">
                    <i class="fa-solid fa-folder"></i>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($proj['name']) ?></div>
                    <div class="proj-card-meta">
                        <span><i class="fa-solid fa-list-check"></i> <?= $proj['done'] ?>/<?= $proj['total'] ?></span>
                        <span><i class="fa-solid fa-users"></i> <?= $proj['member_count'] ?></span>
                    </div>
                </div>
                <span style="font-size:13px;font-weight:800;color:<?= $proj['color'] ?>"><?= $pct ?>%</span>
            </div>
            <div class="progress-bar-track">
                <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $proj['color'] ?>"></div>
            </div>
        </div>
        <?php endforeach; else: ?>
        <div class="empty-state-premium">
            <div class="es-icon">đź“</div>
            <div class="es-title">Brak projektĂłw</div>
            <div class="es-sub">StwĂłrz pierwszy projekt i zaproĹ› zespĂłĹ‚ do pracy.</div>
            <a href="/pages/projects.php" class="es-btn"><i class="fa-solid fa-plus"></i> Nowy projekt</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Activity Feed -->
    <div class="project-list">
        <div class="section-header">
            <h3 class="section-title-premium"><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary)"></i> AktywnoĹ›Ä‡</h3>
        </div>
        <?php if (!empty($activity_logs)): ?>
        <div class="activity-feed">
        <?php foreach (array_slice($activity_logs, 0, 7) as $log): ?>
        <div class="af-item">
            <div class="af-dot" style="background:var(--primary-light);color:var(--primary);font-weight:700;font-size:11px">
                <?= strtoupper(substr($log['full_name'] ?? 'S', 0, 1)) ?>
            </div>
            <div class="af-content">
                <div class="af-who"><?= sanitize($log['full_name'] ?? 'System') ?></div>
                <div class="af-what"><?= sanitize($log['action']) ?></div>
                <div class="af-when"><?= date('d.m H:i', strtotime($log['created_at'])) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-inline">
            <div class="empty-inline-icon">đź””</div>
            <span>Brak aktywnoĹ›ci w workspace</span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Productivity Trend -->
<div class="report-section">
    <div class="section-header">
        <div class="report-title" style="margin:0"><i class="fa-solid fa-chart-line"></i> Trend ProduktywnoĹ›ci (7 dni)</div>
        <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--success)">
            <span style="width:8px;height:8px;border-radius:50%;background:var(--success);animation:pulse 2s infinite;display:inline-block"></span>
            Live
        </span>
    </div>
    <canvas id="trendChart" style="max-height:280px"></canvas>
</div>

<script>
// Priority Chart
const ctxPriority = document.getElementById('priorityChart')?.getContext('2d');
if (ctxPriority) {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const tc = isDark ? '#9ca3af' : '#6b7280';
    new Chart(ctxPriority, {
        type: 'doughnut',
        data: {
            labels: ['Niski', 'Ĺšredni', 'Wysoki', 'Krytyczny'],
            datasets: [{
                data: [<?= implode(',', array_values($priorities_json)) ?>],
                backgroundColor: ['#10b981','#06b6d4','#f59e0b','#ef4444'],
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: tc, padding: 14, font: { size: 12, weight: '600' } }
                }
            }
        }
    });
}

// Trend Chart
let trendChart = null;
async function loadTrend() {
    try {
        const data = await apiGet('/api/stats.php');
        if (!data?.trend) return;
        const labels = data.trend.map(d => d.day);
        const values = data.trend.map(d => d.count);
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
        const textColor = isDark ? '#9ca3af' : '#6b7280';
        const ctx = document.getElementById('trendChart')?.getContext('2d');
        if (!ctx) return;
        if (trendChart) {
            trendChart.data.labels = labels;
            trendChart.data.datasets[0].data = values;
            trendChart.update('active');
            return;
        }
        const grad = ctx.createLinearGradient(0,0,0,280);
        grad.addColorStop(0,'rgba(99,102,241,.25)');
        grad.addColorStop(1,'rgba(99,102,241,.02)');
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Zadania ukoĹ„czone',
                    data: values,
                    borderColor: '#6366f1',
                    backgroundColor: grad,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: textColor } },
                    y: { grid: { color: gridColor }, ticks: { color: textColor, precision: 0 }, beginAtZero: true }
                }
            }
        });
    } catch(e) { console.error('Trend chart error:', e); }
}
loadTrend();
setInterval(loadTrend, 60000);

document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseInt(el.dataset.counter);
    if (!isNaN(target) && typeof animateCounter === 'function') animateCounter(el, target);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
