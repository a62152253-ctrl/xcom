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

// Priority distribution
$stmt_pri = $db->prepare("SELECT t.priority, COUNT(*) as qty FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0 GROUP BY t.priority");
$stmt_pri->execute([$user_id, $user_id]);
$priorities_data = $stmt_pri->fetchAll();
$priorities_json = ['Low' => 0, 'Medium' => 0, 'High' => 0, 'Critical' => 0];
foreach ($priorities_data as $pd) if (isset($priorities_json[$pd['priority']])) $priorities_json[$pd['priority']] = (int)$pd['qty'];

// Activity logs (latest 8)
$stmt_logs = $db->prepare("SELECT l.*, u.full_name FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 8");
$stmt_logs->execute();
$activity_logs = $stmt_logs->fetchAll();

// Top projects by task progress
$stmt_top_proj = $db->prepare("
    SELECT DISTINCT p.id, p.name, p.color,
        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total,
        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status='Done') as done
    FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
    LIMIT 4
");
$stmt_top_proj->execute([$user_id, $user_id]);
$top_projects = $stmt_top_proj->fetchAll();

// Greeting
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Dzień dobry' : ($hour < 18 ? 'Cześć' : 'Dobry wieczór');
?>

<!-- Welcome Banner -->
<div class="dashboard-welcome">
    <div class="dashboard-welcome-text">
        <h1><?= $greeting ?>, <span class="welcome-name"><?= sanitize($user_name) ?></span>! 👋</h1>
        <p>Masz <strong><?= count($tasks_today) ?></strong> zadań na dziś
            <?php if ($overdue_count > 0): ?> i <strong class="text-danger"><?= $overdue_count ?></strong> po terminie<?php endif; ?>.
        </p>
    </div>
    <div class="dashboard-welcome-actions">
        <button class="btn btn-primary" onclick="window.location.href='/pages/tasks.php'" style="width:auto">
            <i class="fa-solid fa-plus"></i> Nowe zadanie
        </button>
        <button class="btn btn-secondary" onclick="window.location.href='/pages/reports.php'" style="width:auto">
            <i class="fa-solid fa-chart-bar"></i> Raporty
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="dashboard-grid">
    <div class="stat-card stat-card--blue" onclick="window.location.href='/pages/projects.php'" style="cursor:pointer">
        <div class="stat-info">
            <h3 data-counter="<?= $projects_count ?>"><?= $projects_count ?></h3>
            <p>Aktywne Projekty</p>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-folder-open"></i></div>
    </div>
    <div class="stat-card stat-card--cyan" onclick="window.location.href='/pages/tasks.php'" style="cursor:pointer">
        <div class="stat-info">
            <h3 data-counter="<?= $active_tasks_count ?>"><?= $active_tasks_count ?></h3>
            <p>Aktywne Zadania</p>
        </div>
        <div class="stat-icon" style="background:rgba(6,182,212,.12);color:#06b6d4"><i class="fa-solid fa-list-check"></i></div>
    </div>
    <div class="stat-card stat-card--green">
        <div class="stat-info">
            <h3 data-counter="<?= $done_count ?>"><?= $done_count ?></h3>
            <p>Ukończone</p>
        </div>
        <div class="stat-icon" style="background:rgba(16,185,129,.12);color:#10b981"><i class="fa-solid fa-circle-check"></i></div>
    </div>
    <div class="stat-card <?= $overdue_count > 0 ? 'stat-card--danger' : '' ?>">
        <div class="stat-info" <?= $overdue_count > 0 ? 'style="color:var(--danger)"' : '' ?>>
            <h3 data-counter="<?= $overdue_count ?>"><?= $overdue_count ?></h3>
            <p>Po terminie</p>
        </div>
        <div class="stat-icon" style="background:rgba(239,68,68,.12);color:var(--danger)"><i class="fa-solid fa-clock"></i></div>
    </div>
</div>

<!-- Main grid -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.25rem;margin-bottom:1.25rem">
    <!-- Today tasks -->
    <div class="card" style="margin-bottom:0">
        <h2 class="card-title"><i class="fa-solid fa-calendar-check"></i> Zadania na dziś
            <span class="nav-badge" style="margin-left:.5rem;background:var(--primary)"><?= count($tasks_today) ?></span>
        </h2>
        <?php if (count($tasks_today) > 0): ?>
        <div class="custom-table-container">
            <table class="custom-table">
                <thead><tr><th>Zadanie</th><th>Projekt</th><th>Priorytet</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($tasks_today as $t): ?>
                <tr onclick="window.location.href='/pages/tasks.php?task_id=<?= $t['id'] ?>'" style="cursor:pointer">
                    <td style="font-weight:600"><?= sanitize($t['name']) ?></td>
                    <td>
                        <span style="display:inline-flex;align-items:center;gap:.4rem">
                            <span style="width:8px;height:8px;border-radius:50%;background:<?= $t['color'] ?>;flex-shrink:0"></span>
                            <?= sanitize($t['project_name']) ?>
                        </span>
                    </td>
                    <td><span class="kanban-card-tag tag-<?= strtolower($t['priority']) ?>"><?= $t['priority'] ?></span></td>
                    <td><span class="badge-pill badge-member" style="font-size:.7rem"><?= $t['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:2.5rem;color:var(--text-muted)">
            <i class="fa-regular fa-face-smile-beam" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.5"></i>
            <p style="font-weight:500">Świetnie! Brak zadań na dziś.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Priority donut -->
    <div class="card" style="margin-bottom:0;display:flex;flex-direction:column">
        <h2 class="card-title"><i class="fa-solid fa-chart-pie"></i> Priorytety</h2>
        <div style="flex:1;display:flex;align-items:center;justify-content:center;min-height:180px">
            <canvas id="priorityChart"></canvas>
        </div>
    </div>
</div>

<!-- Projects progress + Activity -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem">
    <!-- Projects progress -->
    <div class="card" style="margin-bottom:0">
        <h2 class="card-title"><i class="fa-solid fa-folder-open"></i> Postęp projektów</h2>
        <div style="display:flex;flex-direction:column;gap:1rem">
            <?php foreach ($top_projects as $proj):
                $pct = $proj['total'] > 0 ? round($proj['done']/$proj['total']*100) : 0;
            ?>
            <div onclick="window.location.href='/pages/tasks.php?project_id=<?= $proj['id'] ?>'" style="cursor:pointer">
                <div style="display:flex;justify-content:space-between;font-size:.825rem;font-weight:600;margin-bottom:.35rem">
                    <span style="display:flex;align-items:center;gap:.5rem">
                        <span style="width:10px;height:10px;border-radius:50%;background:<?= $proj['color'] ?>"></span>
                        <?= sanitize($proj['name']) ?>
                    </span>
                    <span style="color:var(--text-muted)"><?= $pct ?>%</span>
                </div>
                <div class="progress-bar-track">
                    <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $proj['color'] ?>"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($top_projects)): ?>
            <p style="color:var(--text-muted);text-align:center;padding:1rem;font-size:.85rem">Brak projektów</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Activity Feed -->
    <div class="card" style="margin-bottom:0">
        <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Ostatnia aktywność</h2>
        <div id="activity-feed" style="display:flex;flex-direction:column">
            <?php foreach ($activity_logs as $log): ?>
            <div class="activity-item">
                <div class="activity-avatar"><?= strtoupper(substr($log['full_name'] ?? 'S', 0, 1)) ?></div>
                <div class="activity-body">
                    <strong><?= sanitize($log['full_name'] ?? 'System') ?></strong>
                    <span style="color:var(--text-secondary);font-size:.8rem"> — <?= sanitize($log['action']) ?></span>
                    <?php if ($log['details']): ?>
                    <div class="activity-detail"><?= sanitize(mb_substr($log['details'], 0, 80)) ?></div>
                    <?php endif; ?>
                </div>
                <div class="activity-time"><?= date('H:i', strtotime($log['created_at'])) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- 7-day trend sparkline -->
<div class="card" style="margin-bottom:0">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 class="card-title" style="margin:0"><i class="fa-solid fa-chart-line"></i> Trend produktywności (7 dni)</h2>
        <span id="live-indicator" style="display:flex;align-items:center;gap:.4rem;font-size:.75rem;color:var(--success)">
            <span style="width:8px;height:8px;border-radius:50%;background:var(--success);animation:pulse 2s infinite"></span>
            Live
        </span>
    </div>
    <canvas id="trendChart" height="70"></canvas>
</div>

<script>
// Priority doughnut
const ctxD = document.getElementById('priorityChart')?.getContext('2d');
if (ctxD) {
    new Chart(ctxD, {
        type: 'doughnut',
        data: {
            labels: ['Niski', 'Średni', 'Wysoki', 'Krytyczny'],
            datasets: [{
                data: [<?= implode(',', array_values($priorities_json)) ?>],
                backgroundColor: ['#10b981','#06b6d4','#f59e0b','#ef4444'],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim(),
                        padding: 12,
                        font: { size: 11 }
                    }
                }
            }
        }
    });
}

// Live trend chart
let trendChart = null;

async function loadTrend() {
    const data = await apiGet('/api/stats.php');
    if (!data?.trend) return;

    const labels = data.trend.map(d => d.day);
    const values = data.trend.map(d => d.count);
    const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim();
    const textColor = getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim();

    const ctx = document.getElementById('trendChart')?.getContext('2d');
    if (!ctx) return;

    if (trendChart) {
        trendChart.data.labels = labels;
        trendChart.data.datasets[0].data = values;
        trendChart.update('active');
        return;
    }

    trendChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Ukończone',
                data: values,
                backgroundColor: 'rgba(59,130,246,.7)',
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: textColor, font: { size: 11 } } },
                y: { grid: { color: gridColor }, ticks: { color: textColor, precision: 0, stepSize: 1 }, beginAtZero: true }
            }
        }
    });
}

loadTrend();
setInterval(loadTrend, 60000); // refresh every minute

// Animate stat counters on load
document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseInt(el.dataset.counter);
    if (!isNaN(target) && typeof animateCounter === 'function') animateCounter(el, target);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
