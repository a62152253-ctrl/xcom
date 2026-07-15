<?php
// pages/notifications.php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

$filter = $_GET['filter'] ?? 'all'; // all | unread | read

$where = match($filter) {
    'unread' => 'AND is_read = 0',
    'read'   => 'AND is_read = 1',
    default  => ''
};

$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? $where ORDER BY created_at DESC LIMIT 100");
$stmt->execute([$user_id]);
$notifs = $stmt->fetchAll();

$unread_total = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_total->execute([$user_id]);
$unread_count = $unread_total->fetchColumn();

// Mark unread as read on page load (all visible ones)
if ($filter !== 'read' && $unread_count > 0) {
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
}

$type_icons = [
    'task_assign'   => ['fa-user-check', '#3b82f6'],
    'status_change' => ['fa-arrow-right-arrow-left', '#10b981'],
    'comment'       => ['fa-comment', '#8b5cf6'],
    'deadline'      => ['fa-clock', '#ef4444'],
    'system'        => ['fa-gear', '#64748b'],
    'info'          => ['fa-circle-info', '#06b6d4'],
];
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-bell"></i> Powiadomienia</h1>
        <p class="page-subtitle">Centrum wszystkich alertów i wiadomości systemowych.</p>
    </div>
    <div class="notif-header-actions">
        <?php if ($unread_count > 0): ?>
        <button class="btn btn-secondary" onclick="markAllRead()" style="width:auto;font-size:.85rem">
            <i class="fa-solid fa-check-double"></i> Oznacz wszystkie jako przeczytane
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filter tabs -->
<div class="filter-tabs" style="margin-bottom:1.5rem">
    <a href="?filter=all"    class="filter-tab <?= $filter==='all'    ? 'active':'' ?>">Wszystkie (<?= count($notifs) ?>)</a>
    <a href="?filter=unread" class="filter-tab <?= $filter==='unread' ? 'active':'' ?>">Nieprzeczytane (<?= $unread_count ?>)</a>
    <a href="?filter=read"   class="filter-tab <?= $filter==='read'   ? 'active':'' ?>">Przeczytane</a>
</div>

<!-- Notifications list -->
<?php if (empty($notifs)): ?>
<div class="empty-state">
    <i class="fa-solid fa-bell-slash"></i>
    <h3>Brak powiadomień</h3>
    <p>Wszystko jest na bieżąco!</p>
</div>
<?php else: ?>
<div class="notif-full-list">
    <?php foreach ($notifs as $n):
        [$n_icon, $n_color] = $type_icons[$n['type']] ?? $type_icons['info'];
        $time_ago = time() - strtotime($n['created_at']);
        $time_str = $time_ago < 3600 ? round($time_ago/60).' min temu'
            : ($time_ago < 86400 ? round($time_ago/3600).' godz. temu'
            : date('d.m.Y', strtotime($n['created_at'])));
    ?>
    <div class="notif-full-item <?= !$n['is_read'] ? 'notif-unread' : '' ?>">
        <div class="notif-full-icon" style="background:<?= $n_color ?>20;color:<?= $n_color ?>">
            <i class="fa-solid <?= $n_icon ?>"></i>
        </div>
        <div class="notif-full-body">
            <div class="notif-full-title"><?= sanitize($n['title']) ?></div>
            <div class="notif-full-message"><?= sanitize($n['message']) ?></div>
        </div>
        <div class="notif-full-time"><?= $time_str ?></div>
        <button class="notif-delete-btn" onclick="deleteNotif(<?= $n['id'] ?>, this)" title="Usuń">
            <i class="fa-solid fa-times"></i>
        </button>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
async function markAllRead() {
    const res = await fetch('/api/notifications.php?action=mark_all_read', { method: 'POST' });
    const json = await res.json();
    if (json.success) location.reload();
}

async function deleteNotif(id, btn) {
    const res = await fetch('/api/notifications.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id })
    });
    const json = await res.json();
    if (json.success) btn.closest('.notif-full-item').style.animation = 'fadeOutRight .3s forwards';
    setTimeout(() => btn.closest('.notif-full-item')?.remove(), 300);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
