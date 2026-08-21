<!-- ═══ MAIN GRID ═════════════════════════════════════════════════════════════════ -->
<div class="tasks-grid">

    <!-- Today's tasks -->
    <div class="task-card">
        <div class="section-header">
            <h3 class="section-title-premium">
                <i class="fa-solid fa-calendar-check" style="color:var(--primary)"></i>
                Zadania na dziś
                <span class="section-badge"><?= count($tasks_today) ?></span>
            </h3>
            <a href="/pages/tasks.php" style="font-size:12px;color:var(--primary);font-weight:600;text-decoration:none">
                Wszystkie →
            </a>
        </div>

        <?php if (count($tasks_today) > 0): ?>
            <?php foreach ($tasks_today as $t): ?>
            <div class="task-premium" onclick="window.location.href='/pages/tasks.php?task_id=<?= (int)$t['id'] ?>'">
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
                <div class="es-icon">☀️</div>
                <div class="es-title">Brak zadań na dziś!</div>
                <div class="es-sub">Masz wolny dzień albo już wszystko ukończone. Świetna robota!</div>
                <a href="/pages/tasks.php" class="es-btn"><i class="fa-solid fa-plus"></i> Utwórz zadanie</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Priority Chart -->
    <div class="chart-container">
        <h3><i class="fa-solid fa-chart-pie"></i> Rozkład Priorytetów</h3>
        <canvas id="priorityChart" style="max-height:250px"></canvas>
    </div>
</div>

<!-- ═══ PROJECTS & ACTIVITY ═══════════════════════════════════════════════════════ -->
<div class="projects-row">

    <!-- Projects Progress -->
    <div class="project-list">
        <div class="section-header">
            <h3 class="section-title-premium"><i class="fa-solid fa-chart-line" style="color:var(--primary)"></i> Projekty</h3>
            <a href="/pages/projects.php" style="font-size:12px;color:var(--primary);font-weight:600;text-decoration:none">Wszystkie →</a>
        </div>

        <?php if (!empty($top_projects)):
            foreach ($top_projects as $proj):
                $pct = $proj['total'] > 0 ? round($proj['done']/$proj['total']*100) : 0;
        ?>
        <div class="proj-card-premium" style="--proj-color:<?= htmlspecialchars($proj['color'], ENT_QUOTES) ?>"
             onclick="window.location.href='/pages/tasks.php?project_id=<?= (int)$proj['id'] ?>'">
            <div class="proj-card-top">
                <div class="proj-color-dot" style="background:<?= $proj['color'] ?>22;color:<?= $proj['color'] ?>">
                    <i class="fa-solid fa-folder"></i>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($proj['name']) ?></div>
                    <div class="proj-card-meta">
                        <span><i class="fa-solid fa-list-check"></i> <?= $proj['done'] ?>/<?= $proj['total'] ?></span>
                        <span><i class="fa-solid fa-users"></i> <?= $proj['member_count'] ?? 0 ?></span>
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
            <div class="es-icon">📁</div>
            <div class="es-title">Brak projektów</div>
            <div class="es-sub">Stwórz pierwszy projekt i zaproś zespół do pracy.</div>
            <a href="/pages/projects.php" class="es-btn"><i class="fa-solid fa-plus"></i> Nowy projekt</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Activity Feed -->
    <div class="project-list">
        <div class="section-header">
            <h3 class="section-title-premium"><i class="fa-solid fa-clock-rotate-left" style="color:var(--primary)"></i> Aktywność</h3>
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
            <div class="empty-inline-icon">🔔</div>
            <span>Brak aktywności w workspace</span>
        </div>
        <?php endif; ?>
    </div>
</div>

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
// Priority Chart
const ctxPriority = document.getElementById('priorityChart')?.getContext('2d');
if (ctxPriority) {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const tc = isDark ? '#9ca3af' : '#6b7280';
    new Chart(ctxPriority, {
        type: 'doughnut',
        data: {
            labels: ['Niski', 'Średni', 'Wysoki', 'Krytyczny'],
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
                    label: 'Zadania ukończone',
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
document.addEventListener("DOMContentLoaded", () => { loadTrend(); setInterval(loadTrend, 60000); });

document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseInt(el.dataset.counter);
    if (!isNaN(target) && typeof animateCounter === 'function') animateCounter(el, target);
});
</script>
