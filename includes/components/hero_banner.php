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
