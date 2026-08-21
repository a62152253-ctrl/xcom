<!-- ═══ PREMIUM KPI CARDS ════════════════════════════════════════════════════════ -->
<div class="pkpi-grid">

    <div class="pkpi-card pkpi-card-projects" onclick="window.location.href='/pages/projects.php'">
        <div class="pkpi-top">
            <div class="pkpi-icon"><i class="fa-solid fa-folder-open"></i></div>
            <span class="pkpi-trend flat"><i class="fa-solid fa-minus"></i> aktywne</span>
        </div>
        <div class="pkpi-value" data-counter="<?= $projects_count ?>"><?= $projects_count ?></div>
        <div class="pkpi-label">Projekty</div>
        <div class="pkpi-sub">Kliknij aby zobaczyć wszystkie →</div>
    </div>

    <div class="pkpi-card pkpi-card-tasks" onclick="window.location.href='/pages/tasks.php'">
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

    <div class="pkpi-card pkpi-card-done">
        <div class="pkpi-top">
            <div class="pkpi-icon"><i class="fa-solid fa-circle-check"></i></div>
            <span class="pkpi-trend up"><i class="fa-solid fa-arrow-up"></i> ukończone</span>
        </div>
        <div class="pkpi-value" data-counter="<?= $done_count ?>"><?= $done_count ?></div>
        <div class="pkpi-label">Ukończone</div>
        <div class="pkpi-sub"><?= $ws_pct ?>% wszystkich zadań</div>
    </div>

    <div class="pkpi-card <?= $overdue_count > 0 ? 'pkpi-card-overdue' : 'pkpi-card-ok' ?>">
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
