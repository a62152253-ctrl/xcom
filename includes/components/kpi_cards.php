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
