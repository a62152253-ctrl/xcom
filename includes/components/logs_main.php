<!-- Main Logs -->
    <div class="logs-main">
        <?php if (empty($logs)): ?>
        <div class="empty-state-premium" style="max-width:420px;margin:40px auto">
            <div class="es-icon">📋</div>
            <div class="es-title">Brak logów do wyświetlenia</div>
            <div class="es-sub">Historia aktywności w workspace jest pusta. Wszystkie ważne zdarzenia pojawią się tutaj.</div>
        </div>
        <?php else: ?>
        <div class="logs-timeline">
            <?php foreach ($logs as $log):
                $icon_data = get_action_icon_color($log['action']);
            ?>
            <div class="log-entry">
                <div class="log-card">
                    <div class="log-header">
                        <div class="log-icon" style="background: <?= $icon_data['bg'] ?>; color: <?= $icon_data['color'] ?>;">
                            <i class="fa-solid <?= $icon_data['icon'] ?>"></i>
                        </div>
                        <div class="log-info">
                            <div class="log-user"><?= sanitize($log['full_name'] ?? 'System') ?></div>
                            <?php if (!empty($log['email'])): ?>
                            <div class="log-email"><?= sanitize($log['email']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="log-time">
                            <i class="fa-regular fa-clock"></i>
                            <?= date('d.m.Y H:i', strtotime($log['created_at'])) ?>
                        </div>
                    </div>
                    <div class="log-action">
                        <span class="log-action-code"><?= str_replace('_', ' ', sanitize($log['action'])) ?></span>
                        <?php if (!empty($log['description'])): ?>
                        <div style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--text-secondary); opacity: 0.8;">
                            <?= sanitize($log['description']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <button class="pagination-btn" onclick="goToPage(<?= max(1, $page - 1) ?>)" <?= $page <= 1 ? 'disabled' : '' ?>>
                <i class="fa-solid fa-chevron-left"></i> Wstecz
            </button>
            <span class="pagination-info">
                <?= $page ?> / <?= $total_pages ?> • <?= $total ?> logów
            </span>
            <button class="pagination-btn" onclick="goToPage(<?= min($total_pages, $page + 1) ?>)" <?= $page >= $total_pages ? 'disabled' : '' ?>>
                Dalej <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>