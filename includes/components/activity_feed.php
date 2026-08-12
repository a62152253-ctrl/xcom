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
