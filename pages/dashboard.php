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
$greeting = $hour < 12 ? 'Dzień dobry' : ($hour < 18 ? 'Cześć' : 'Dobry wieczór');
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

<!-- Hero Welcome Banner -->
<div class="dashboard-hero animate-fade">
    <h1><?= $greeting ?>, <span class="welcome-name"><?= sanitize($user_name) ?></span>! 👋</h1>
    <p>
        Masz <strong><?= count($tasks_today) ?></strong> zadań na dziś
        <?php if ($overdue_count > 0): ?>
            i <strong style="background: rgba(255,255,255,0.35); padding: 0.25rem 0.5rem; border-radius: 4px;"><?= $overdue_count ?></strong> po terminie
        <?php endif; ?>
    </p>
    <div class="dashboard-actions">
        <button class="btn btn-primary" onclick="window.location.href='/pages/tasks.php'">
            <i class="fa-solid fa-plus"></i> Nowe zadanie
        </button>
        <button class="btn" style="background: rgba(255,255,255,0.2); color: white;" onclick="window.location.href='/pages/projects.php'">
            <i class="fa-solid fa-folder-plus"></i> Nowy projekt
        </button>
        <button class="btn" style="background: rgba(255,255,255,0.2); color: white;" onclick="window.location.href='/pages/reports.php'">
            <i class="fa-solid fa-chart-bar"></i> Raporty
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="kpi-grid">
    <div class="kpi-card" onclick="window.location.href='/pages/projects.php'">
        <div class="kpi-card-content">
            <div class="kpi-card-info">
                <h3 data-counter="<?= $projects_count ?>"><?= $projects_count ?></h3>
                <p>Aktywne Projekty</p>
            </div>
            <div class="kpi-card-icon">
                <i class="fa-solid fa-folder-open"></i>
            </div>
        </div>
    </div>

    <div class="kpi-card" onclick="window.location.href='/pages/tasks.php'">
        <div class="kpi-card-content">
            <div class="kpi-card-info">
                <h3 data-counter="<?= $active_tasks_count ?>"><?= $active_tasks_count ?></h3>
                <p>Aktywne Zadania</p>
            </div>
            <div class="kpi-card-icon">
                <i class="fa-solid fa-list-check"></i>
            </div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card-content">
            <div class="kpi-card-info">
                <h3 data-counter="<?= $done_count ?>"><?= $done_count ?></h3>
                <p>Ukończone</p>
            </div>
            <div class="kpi-card-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
    </div>

    <div class="kpi-card" style="<?= $overdue_count > 0 ? 'border-top: 4px solid var(--danger);' : '' ?>">
        <div class="kpi-card-content">
            <div class="kpi-card-info">
                <h3 data-counter="<?= $overdue_count ?>" style="color: <?= $overdue_count > 0 ? 'var(--danger)' : 'var(--primary)' ?>"><?= $overdue_count ?></h3>
                <p>Po terminie</p>
            </div>
            <div class="kpi-card-icon">
                <i class="fa-solid fa-clock"></i>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="tasks-grid">
    <!-- Today Tasks -->
    <div class="task-card">
        <h3><i class="fa-solid fa-calendar-check"></i> Zadania na dziś (<?= count($tasks_today) ?>)</h3>
        <?php if (count($tasks_today) > 0): ?>
            <?php foreach ($tasks_today as $t): ?>
            <div class="task-item" onclick="window.location.href='/pages/tasks.php?task_id=<?= $t['id'] ?>'">
                <div class="task-item-dot" style="background: <?= $t['color'] ?>"></div>
                <div class="task-item-info">
                    <div class="task-item-name"><?= sanitize($t['name']) ?></div>
                    <div class="task-item-project">
                        <i class="fa-solid fa-folder"></i>
                        <?= sanitize($t['project_name']) ?>
                    </div>
                </div>
                <span class="badge badge-<?= strtolower($t['priority']) ?>" style="font-size: 0.75rem;">
                    <?= $t['priority'] ?>
                </span>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-regular fa-face-smile"></i>
                <p>Świetnie! Brak zadań na dziś.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Priority Chart -->
    <div class="chart-container">
        <h3><i class="fa-solid fa-chart-pie"></i> Rozkład Priorytetów</h3>
        <canvas id="priorityChart" style="max-height: 250px;"></canvas>
    </div>
</div>

<!-- Projects & Activity -->
<div class="projects-row">
    <!-- Projects Progress -->
    <div class="project-list">
        <h3><i class="fa-solid fa-chart-line"></i> Postęp Projektów (<?= count($top_projects) ?>)</h3>
        <?php if (!empty($top_projects)): ?>
            <?php foreach ($top_projects as $proj):
                $pct = $proj['total'] > 0 ? round($proj['done']/$proj['total']*100) : 0;
            ?>
            <div class="project-item" onclick="window.location.href='/pages/tasks.php?project_id=<?= $proj['id'] ?>'">
                <div class="project-header">
                    <div class="project-name">
                        <span style="width: 10px; height: 10px; border-radius: 50%; background: <?= $proj['color'] ?>; flex-shrink: 0;"></span>
                        <?= sanitize($proj['name']) ?>
                    </div>
                    <span class="project-percent"><?= $pct ?>%</span>
                </div>
                <div class="progress-compact">
                    <div class="progress-compact-bar" style="width: <?= $pct ?>%; background: <?= $proj['color'] ?>;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <p>Brak projektów. Utwórz pierwszy!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Activity Feed -->
    <div class="project-list">
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Ostatnia Aktywność</h3>
        <?php if (!empty($activity_logs)): ?>
            <?php foreach (array_slice($activity_logs, 0, 6) as $log): ?>
            <div class="project-item" style="cursor: default; margin-bottom: 0.5rem;">
                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--primary); font-size: 0.85rem; flex-shrink: 0;">
                        <?= strtoupper(substr($log['full_name'] ?? 'S', 0, 1)) ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-primary);">
                            <?= sanitize($log['full_name'] ?? 'System') ?>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                            <?= sanitize($log['action']) ?>
                        </div>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); white-space: nowrap;">
                        <?= date('H:i', strtotime($log['created_at'])) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="padding: 1rem;">
                <p style="font-size: 0.9rem;">Brak aktywności</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Productivity Trend -->
<div class="chart-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="margin: 0;"><i class="fa-solid fa-chart-line"></i> Trend Produktywności (7 dni)</h3>
        <span style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--success);">
            <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--success); animation: pulse 2s infinite;"></span>
            Live
        </span>
    </div>
    <canvas id="trendChart" style="max-height: 300px;"></canvas>
</div>

<script>
// Priority Chart
const ctxPriority = document.getElementById('priorityChart')?.getContext('2d');
if (ctxPriority) {
    new Chart(ctxPriority, {
        type: 'doughnut',
        data: {
            labels: ['Niski', 'Średni', 'Wysoki', 'Krytyczny'],
            datasets: [{
                data: [<?= implode(',', array_values($priorities_json)) ?>],
                backgroundColor: ['#10b981', '#06b6d4', '#f59e0b', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim(),
                        padding: 15,
                        font: { size: 12, weight: '600' }
                    }
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
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Zadania ukończone',
                    data: values,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: textColor } },
                    y: { grid: { color: gridColor }, ticks: { color: textColor, precision: 0 }, beginAtZero: true }
                }
            }
        });
    } catch (e) {
        console.error('Trend chart error:', e);
    }
}

loadTrend();
setInterval(loadTrend, 60000);

document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseInt(el.dataset.counter);
    if (!isNaN(target) && typeof animateCounter === 'function') animateCounter(el, target);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
