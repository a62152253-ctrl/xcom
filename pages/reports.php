<?php
// pages/reports.php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// 1. Tasks completed per day last 14 days
$stmt_daily = $db->prepare("
    SELECT DATE(updated_at) as day, COUNT(*) as cnt
    FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND t.status = 'Done'
      AND t.updated_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(updated_at) ORDER BY day ASC
");
$stmt_daily->execute([$user_id, $user_id]);
$daily_data = $stmt_daily->fetchAll();

// Build 14-day array
$days_labels = [];
$days_values = [];
$daily_map = [];
foreach ($daily_data as $d) $daily_map[$d['day']] = $d['cnt'];
for ($i = 13; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $days_labels[] = date('d.m', strtotime($day));
    $days_values[] = $daily_map[$day] ?? 0;
}

// 2. Tasks by status
$stmt_status = $db->prepare("
    SELECT t.status, COUNT(*) as cnt FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
    GROUP BY t.status
");
$stmt_status->execute([$user_id, $user_id]);
$status_rows = $stmt_status->fetchAll(PDO::FETCH_KEY_PAIR);
$statuses = ['To Do' => 0, 'In Progress' => 0, 'Review' => 0, 'Done' => 0];
foreach ($status_rows as $k => $v) $statuses[$k] = (int)$v;

// 3. Top members by completed tasks (all projects user belongs to)
$stmt_top = $db->prepare("
    SELECT u.full_name, COUNT(t.id) as done_cnt
    FROM tasks t
    INNER JOIN users u ON t.assigned_to = u.id
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND t.status = 'Done' AND p.is_archived = 0
    GROUP BY t.assigned_to ORDER BY done_cnt DESC LIMIT 6
");
$stmt_top->execute([$user_id, $user_id]);
$top_members = $stmt_top->fetchAll();

// 4. Projects progress
$stmt_proj = $db->prepare("
    SELECT DISTINCT p.id, p.name, p.color,
        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total,
        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'Done') as done
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
    LIMIT 8
");
$stmt_proj->execute([$user_id, $user_id]);
$proj_progress = $stmt_proj->fetchAll();

// 5. Summary numbers
$total_done = array_sum(array_column($daily_data, 'cnt'));
$total_tasks = array_sum($statuses);
$pct_done = $total_tasks > 0 ? round($statuses['Done'] / $total_tasks * 100) : 0;
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-chart-bar"></i> Raporty i Analityka</h1>
        <p class="page-subtitle">Przegląd produktywności, postępów projektów i aktywności zespołu.</p>
    </div>
</div>

<!-- Summary KPI strip -->
<div class="kpi-strip">
    <div class="kpi-card">
        <div class="kpi-icon" style="background:rgba(59,130,246,.15);color:#3b82f6"><i class="fa-solid fa-circle-check"></i></div>
        <div>
            <div class="kpi-value"><?= $statuses['Done'] ?></div>
            <div class="kpi-label">Ukończone zadania</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:rgba(245,158,11,.15);color:#f59e0b"><i class="fa-solid fa-spinner"></i></div>
        <div>
            <div class="kpi-value"><?= $statuses['In Progress'] ?></div>
            <div class="kpi-label">W trakcie</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:rgba(239,68,68,.15);color:#ef4444"><i class="fa-solid fa-list"></i></div>
        <div>
            <div class="kpi-value"><?= $statuses['To Do'] ?></div>
            <div class="kpi-label">Do zrobienia</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:rgba(16,185,129,.15);color:#10b981"><i class="fa-solid fa-percent"></i></div>
        <div>
            <div class="kpi-value"><?= $pct_done ?>%</div>
            <div class="kpi-label">Ukończono ogółem</div>
        </div>
    </div>
</div>

<!-- Charts row -->
<div class="reports-grid">
    <!-- Line chart: tasks done per day -->
    <div class="card reports-chart-card">
        <h2 class="card-title"><i class="fa-solid fa-chart-line"></i> Zadania ukończone (14 dni)</h2>
        <canvas id="lineChart" height="120"></canvas>
    </div>

    <!-- Doughnut: status -->
    <div class="card reports-chart-card">
        <h2 class="card-title"><i class="fa-solid fa-chart-pie"></i> Rozkład statusów</h2>
        <canvas id="donutChart" height="120"></canvas>
    </div>
</div>

<!-- Projects progress -->
<div class="card" style="margin-bottom:1.5rem">
    <h2 class="card-title"><i class="fa-solid fa-folder-open"></i> Postęp projektów</h2>
    <div class="proj-progress-list">
        <?php foreach ($proj_progress as $pr):
            $pct = $pr['total'] > 0 ? round($pr['done'] / $pr['total'] * 100) : 0;
        ?>
        <div class="proj-progress-item">
            <div class="proj-progress-label">
                <span class="proj-dot" style="background:<?= $pr['color'] ?>"></span>
                <span><?= sanitize($pr['name']) ?></span>
                <span class="proj-progress-nums"><?= $pr['done'] ?>/<?= $pr['total'] ?></span>
            </div>
            <div class="progress-bar-track">
                <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $pr['color'] ?>"></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($proj_progress)): ?>
        <p style="color:var(--text-muted);text-align:center;padding:2rem">Brak projektów do wyświetlenia.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Top members -->
<?php if (!empty($top_members)): ?>
<div class="card">
    <h2 class="card-title"><i class="fa-solid fa-trophy"></i> Ranking produktywności</h2>
    <div class="ranking-list">
        <?php foreach ($top_members as $i => $m): ?>
        <div class="ranking-item">
            <span class="ranking-pos ranking-pos-<?= $i+1 ?>"><?= $i+1 ?></span>
            <div class="user-avatar" style="width:34px;height:34px;font-size:.85rem"><?= strtoupper(substr($m['full_name'],0,1)) ?></div>
            <span class="ranking-name"><?= sanitize($m['full_name']) ?></span>
            <span class="ranking-badge"><?= $m['done_cnt'] ?> ukończonych</span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
const chartColor = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim();
const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--border-color').trim();

// Line chart
new Chart(document.getElementById('lineChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?= json_encode($days_labels) ?>,
        datasets: [{
            label: 'Ukończone zadania',
            data: <?= json_encode($days_values) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,.12)',
            borderWidth: 2,
            tension: .4,
            fill: true,
            pointBackgroundColor: '#3b82f6',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: chartColor } },
            y: { grid: { color: gridColor }, ticks: { color: chartColor, stepSize: 1, precision: 0 } }
        }
    }
});

// Doughnut chart
new Chart(document.getElementById('donutChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Do zrobienia', 'W trakcie', 'Review', 'Ukończone'],
        datasets: [{
            data: [<?= $statuses['To Do'] ?>, <?= $statuses['In Progress'] ?>, <?= $statuses['Review'] ?>, <?= $statuses['Done'] ?>],
            backgroundColor: ['#64748b','#f59e0b','#06b6d4','#10b981'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { color: chartColor, padding: 16 } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
