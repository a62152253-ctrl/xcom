<?php
// pages/archive.php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT DISTINCT p.*, u.full_name as creator_name,
        (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count,
        (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN users u ON p.created_by = u.id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 1
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$archived = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-box-archive"></i> Archiwum projektów</h1>
        <p class="page-subtitle">Zarchiwizowane projekty — możesz je przywrócić w każdej chwili.</p>
    </div>
    <span class="files-count-badge"><?= count($archived) ?> projektów</span>
</div>

<?php if (empty($archived)): ?>
<div class="empty-state">
    <i class="fa-solid fa-box-archive"></i>
    <h3>Brak zarchiwizowanych projektów</h3>
    <p>Projekty, które zarchiwizujesz z poziomu zarządzania projektami, pojawią się tutaj.</p>
</div>
<?php else: ?>
<div class="archive-grid">
    <?php foreach ($archived as $p): ?>
    <div class="archive-card">
        <div class="archive-bar" style="background:<?= $p['color'] ?>"></div>
        <div class="archive-body">
            <h3 class="archive-name"><?= sanitize($p['name']) ?></h3>
            <p class="archive-desc"><?= sanitize(mb_substr($p['description'] ?? '', 0, 100)) ?></p>
            <div class="archive-meta">
                <span><i class="fa-solid fa-list-check"></i> <?= $p['task_count'] ?> zadań</span>
                <span><i class="fa-solid fa-users"></i> <?= $p['member_count'] ?> członków</span>
                <span><i class="fa-regular fa-calendar"></i> <?= date('d.m.Y', strtotime($p['created_at'])) ?></span>
            </div>
        </div>
        <div class="archive-actions">
            <button class="btn btn-primary" style="width:auto;font-size:.85rem" onclick="restoreProject(<?= $p['id'] ?>)">
                <i class="fa-solid fa-rotate-left"></i> Przywróć
            </button>
            <button class="btn btn-secondary" style="width:auto;font-size:.85rem;color:var(--danger)" onclick="deleteProject(<?= $p['id'] ?>)">
                <i class="fa-solid fa-trash"></i> Usuń
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
async function restoreProject(id) {
    if (!confirm('Przywrócić projekt?')) return;
    const res = await fetch('/api/projects.php?action=restore', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id })
    });
    const json = await res.json();
    if (json.success) location.reload();
    else alert(json.error || 'Błąd przywracania');
}

async function deleteProject(id) {
    if (!confirm('TRWALE usunąć ten projekt i wszystkie jego zadania? Tej operacji nie można cofnąć.')) return;
    const res = await fetch('/api/projects.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id })
    });
    const json = await res.json();
    if (json.success) location.reload();
    else alert(json.error || 'Błąd usuwania');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
