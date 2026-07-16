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
                <div class="es-icon">☀️</div>
                <div class="es-title">Brak zadań na dziś!</div>
                <div class="es-sub">Nie masz przypisanych zadań na dziś. Odpocznij lub zaplanuj kolejne!</div>
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