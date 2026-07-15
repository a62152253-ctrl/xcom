<?php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

$stmt_t_done = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'Done'");
$stmt_t_done->execute([$user_id]);
$completed_tasks = (int)$stmt_t_done->fetchColumn();

$stmt_t_all = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ?");
$stmt_t_all->execute([$user_id]);
$assigned_tasks = (int)$stmt_t_all->fetchColumn();

$stmt_t_progress = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status = 'In Progress'");
$stmt_t_progress->execute([$user_id]);
$in_progress_tasks = (int)$stmt_t_progress->fetchColumn();

$pct_done = $assigned_tasks > 0 ? round($completed_tasks / $assigned_tasks * 100) : 0;

// Projects count
$stmt_proj = $db->prepare("SELECT COUNT(DISTINCT p.id) FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0");
$stmt_proj->execute([$user_id, $user_id]);
$projects_count = (int)$stmt_proj->fetchColumn();

// Activity logs
$stmt_logs = $db->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt_logs->execute([$user_id]);
$logs = $stmt_logs->fetchAll();

// Activity heatmap: last 35 days
$stmt_heat = $db->prepare("SELECT DATE(created_at) as day, COUNT(*) as cnt FROM activity_logs WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 35 DAY) GROUP BY DATE(created_at)");
$stmt_heat->execute([$user_id]);
$heat_raw = $stmt_heat->fetchAll(PDO::FETCH_KEY_PAIR);
$heat_days = [];
for ($i = 34; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $heat_days[$d] = $heat_raw[$d] ?? 0;
}
$max_heat = max(max($heat_days), 1);
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-user-circle"></i> Mój profil</h1>
        <p class="page-subtitle">Twoje statystyki, aktywność i historia.</p>
    </div>
    <a href="/pages/settings.php" class="btn btn-secondary" style="width:auto">
        <i class="fa-solid fa-pen-to-square"></i> Edytuj profil
    </a>
</div>

<div class="profile-grid">
    <!-- Left: Profile card -->
    <div>
        <div class="card profile-card">
            <!-- Avatar with progress ring -->
            <div class="profile-avatar-wrap">
                <svg class="progress-ring-svg" width="120" height="120">
                    <circle cx="60" cy="60" r="52" fill="none" stroke="var(--border-color)" stroke-width="6"/>
                    <circle cx="60" cy="60" r="52" fill="none" stroke="var(--primary)" stroke-width="6"
                        stroke-dasharray="<?= round(2 * M_PI * 52) ?>"
                        stroke-dashoffset="<?= round(2 * M_PI * 52 * (1 - $pct_done / 100)) ?>"
                        stroke-linecap="round" transform="rotate(-90 60 60)"
                        style="transition:stroke-dashoffset 1s ease"/>
                </svg>
                <?php if (!empty($profile['avatar'])): ?>
                    <img src="<?= sanitize($profile['avatar']) ?>" class="profile-avatar-img">
                <?php else: ?>
                    <div class="profile-avatar-circle"><?= strtoupper(substr($profile['full_name'], 0, 1)) ?></div>
                <?php endif; ?>
                <div class="profile-ring-pct"><?= $pct_done ?>%</div>
            </div>

            <h2 class="profile-name"><?= sanitize($profile['full_name']) ?></h2>
            <p class="profile-email"><?= sanitize($profile['email']) ?></p>
            <span class="badge-pill badge-<?= strtolower($profile['role'] === 'Owner' ? 'owner' : ($profile['role'] === 'Administrator' ? 'admin' : 'member')) ?>" style="margin-bottom:1.5rem">
                <?= $profile['role'] ?>
            </span>

            <!-- Stats -->
            <div class="profile-stats-grid">
                <div class="profile-stat">
                    <strong><?= $assigned_tasks ?></strong>
                    <span>Przypisane</span>
                </div>
                <div class="profile-stat">
                    <strong><?= $completed_tasks ?></strong>
                    <span>Ukończone</span>
                </div>
                <div class="profile-stat">
                    <strong><?= $in_progress_tasks ?></strong>
                    <span>W toku</span>
                </div>
                <div class="profile-stat">
                    <strong><?= $projects_count ?></strong>
                    <span>Projekty</span>
                </div>
            </div>

            <!-- Member since -->
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:1rem;text-align:center">
                <i class="fa-regular fa-calendar"></i>
                Członek od <?= date('F Y', strtotime($profile['created_at'])) ?>
            </div>
        </div>

        <!-- Activity Heatmap -->
        <div class="card" style="margin-top:1.25rem">
            <h2 class="card-title"><i class="fa-solid fa-fire"></i> Aktywność (35 dni)</h2>
            <div class="heatmap-grid">
                <?php foreach ($heat_days as $day => $cnt):
                    $intensity = $cnt === 0 ? 0 : max(1, round($cnt / $max_heat * 4));
                    $day_label = date('d.m', strtotime($day));
                ?>
                <div class="heatmap-cell heatmap-<?= $intensity ?>" title="<?= $day_label ?>: <?= $cnt ?> akcji"></div>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--text-muted);margin-top:.5rem">
                <span>35 dni temu</span>
                <span>Dziś</span>
            </div>
        </div>
    </div>

    <!-- Right: Activity log -->
    <div class="card" style="margin-bottom:0">
        <h2 class="card-title"><i class="fa-solid fa-list-ul"></i> Historia aktywności</h2>
        <div style="display:flex;flex-direction:column;gap:0">
            <?php if (empty($logs)): ?>
            <div class="empty-state" style="padding:2rem">
                <i class="fa-solid fa-history"></i>
                <p>Brak zapisanej aktywności.</p>
            </div>
            <?php else: ?>
            <?php foreach ($logs as $log): ?>
            <div class="activity-item">
                <div class="activity-avatar" style="background:var(--primary-light);color:var(--primary)">
                    <i class="fa-solid fa-bolt" style="font-size:.7rem"></i>
                </div>
                <div class="activity-body">
                    <span class="activity-action-tag"><?= sanitize($log['action']) ?></span>
                    <?php if ($log['details']): ?>
                    <div class="activity-detail"><?= sanitize(mb_substr($log['details'], 0, 100)) ?></div>
                    <?php endif; ?>
                </div>
                <div class="activity-time">
                    <?= date('H:i', strtotime($log['created_at'])) ?><br>
                    <span style="font-size:.65rem"><?= date('d.m', strtotime($log['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
