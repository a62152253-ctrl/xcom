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