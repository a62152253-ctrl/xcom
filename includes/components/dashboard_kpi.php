<!-- ═══ PREMIUM KPI CARDS ════════════════════════════════════════════════════════ -->
<div class="pkpi-grid">

    <div class="pkpi-card" style="--pkpi-color:#6366f1;--pkpi-light:rgba(99,102,241,.1);--pkpi-grad:linear-gradient(90deg,#6366f1,#8b5cf6);--pkpi-glow:rgba(99,102,241,.1)"
         onclick="window.location.href='/pages/projects.php'">
        <div class="pkpi-top">
            <div class="pkpi-icon"><i class="fa-solid fa-folder-open"></i></div>
            <span class="pkpi-trend flat"><i class="fa-solid fa-minus"></i> aktywne</span>
        </div>
        <div class="pkpi-value" data-counter="<?php echo $projects_count; ?>"><?php echo $projects_count; ?></div>
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
        <div class="pkpi-value" data-counter="<?php echo $active_tasks_count; ?>"><?php echo $active_tasks_count; ?></div>
        <div class="pkpi-label">Aktywne zadania</div>
        <div class="pkpi-sub"><?php echo $done_count; ?> ukończonych łącznie</div>
    </div>

    <div class="pkpi-card" style="--pkpi-color:#22c55e;--pkpi-light:rgba(34,197,94,.1);--pkpi-grad:linear-gradient(90deg,#22c55e,#10b981);--pkpi-glow:rgba(34,197,94,.1)">
        <div class="pkpi-top">
            <div class="pkpi-icon"><i class="fa-solid fa-circle-check"></i></div>
            <span class="pkpi-trend up"><i class="fa-solid fa-arrow-up"></i> ukończone</span>
        </div>
        <div class="pkpi-value" data-counter="<?php echo $done_count; ?>"><?php echo $done_count; ?></div>
        <div class="pkpi-label">Ukończone</div>
        <div class="pkpi-sub"><?php echo $ws_pct; ?>% wszystkich zadań</div>
    </div>

    <div class="pkpi-card" style="--pkpi-color:<?php echo $overdue_count > 0 ? '#ef4444' : '#10b981'; ?>;--pkpi-light:<?php echo $overdue_count > 0 ? 'rgba(239,68,68,.1)' : 'rgba(16,185,129,.1)'; ?>;--pkpi-grad:<?php echo $overdue_count > 0 ? 'linear-gradient(90deg,#ef4444,#dc2626)' : 'linear-gradient(90deg,#22c55e,#10b981)'; ?>;--pkpi-glow:rgba(239,68,68,.08)">
        <div class="pkpi-top">
            <div class="pkpi-icon"><i class="fa-solid fa-clock"></i></div>
            <?php if ($overdue_count > 0): ?>
            <span class="pkpi-trend down"><i class="fa-solid fa-triangle-exclamation"></i> pilne</span>
            <?php else: ?>
            <span class="pkpi-trend up"><i class="fa-solid fa-check"></i> ok</span>
            <?php endif; ?>
        </div>
        <div class="pkpi-value" data-counter="<?php echo $overdue_count; ?>"><?php echo $overdue_count; ?></div>
        <div class="pkpi-label">Po terminie</div>
        <div class="pkpi-sub"><?php echo count($tasks_today); ?> zadań na dziś</div>
    </div>

</div>
