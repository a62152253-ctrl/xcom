<?php
// pages/files.php
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Filter by project
$filter_project = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// User's projects (for filter bar)
$stmt_projs = $db->prepare("
    SELECT DISTINCT p.id, p.name, p.color FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
    ORDER BY p.name ASC
");
$stmt_projs->execute([$user_id, $user_id]);
$user_projects = $stmt_projs->fetchAll();

// Files query
$where = $filter_project ? "AND p.id = $filter_project" : '';
$stmt_files = $db->prepare("
    SELECT tf.*, t.name as task_name, t.id as task_id, p.name as project_name, p.color as project_color,
           u.full_name as uploader_name
    FROM task_files tf
    INNER JOIN tasks t ON tf.task_id = t.id
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    INNER JOIN users u ON tf.uploaded_by = u.id
    WHERE (p.created_by = ? OR pm.user_id = ?) $where
    ORDER BY tf.uploaded_at DESC
    LIMIT 100
");
$stmt_files->execute([$user_id, $user_id]);
$files = $stmt_files->fetchAll();

// Icon map for file types
function file_icon($name) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $map = [
        'pdf' => ['fa-file-pdf', '#ef4444'],
        'doc' => ['fa-file-word', '#3b82f6'], 'docx' => ['fa-file-word', '#3b82f6'],
        'xls' => ['fa-file-excel', '#10b981'], 'xlsx' => ['fa-file-excel', '#10b981'],
        'ppt' => ['fa-file-powerpoint', '#f59e0b'], 'pptx' => ['fa-file-powerpoint', '#f59e0b'],
        'jpg' => ['fa-file-image', '#8b5cf6'], 'jpeg' => ['fa-file-image', '#8b5cf6'],
        'png' => ['fa-file-image', '#8b5cf6'], 'gif' => ['fa-file-image', '#8b5cf6'],
        'zip' => ['fa-file-zipper', '#f97316'], 'rar' => ['fa-file-zipper', '#f97316'],
        'txt' => ['fa-file-lines', '#64748b'],
        'php' => ['fa-file-code', '#06b6d4'], 'js' => ['fa-file-code', '#06b6d4'],
        'css' => ['fa-file-code', '#06b6d4'], 'html' => ['fa-file-code', '#06b6d4'],
    ];
    return $map[$ext] ?? ['fa-file', '#94a3b8'];
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-folder-open"></i> Centrum plików</h1>
        <p class="page-subtitle">Wszystkie pliki z Twoich zadań i projektów.</p>
    </div>
    <span class="files-count-badge"><?= count($files) ?> pliki</span>
</div>

<!-- Project filter tabs -->
<div class="filter-tabs" style="margin-bottom:1.5rem">
    <a href="/pages/files.php" class="filter-tab <?= !$filter_project ? 'active' : '' ?>">Wszystkie</a>
    <?php foreach ($user_projects as $p): ?>
    <a href="/pages/files.php?project_id=<?= $p['id'] ?>"
       class="filter-tab <?= $filter_project == $p['id'] ? 'active' : '' ?>"
       style="--tab-color:<?= $p['color'] ?>">
        <span class="filter-tab-dot" style="background:<?= $p['color'] ?>"></span>
        <?= sanitize($p['name']) ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Files grid -->
<?php if (empty($files)): ?>
<div class="empty-state">
    <i class="fa-solid fa-folder-open"></i>
    <h3>Brak plików</h3>
    <p>Pliki pojawią się tutaj po dodaniu załączników do zadań.</p>
</div>
<?php else: ?>
<div class="files-grid">
    <?php foreach ($files as $f):
        [$icon, $icon_color] = file_icon($f['file_name']);
        $size_kb = file_exists($f['file_path']) ? round(filesize($f['file_path']) / 1024, 1) : null;
    ?>
    <div class="file-card">
        <div class="file-icon" style="color:<?= $icon_color ?>">
            <i class="fa-solid <?= $icon ?>"></i>
        </div>
        <div class="file-info">
            <div class="file-name" title="<?= sanitize($f['file_name']) ?>"><?= sanitize($f['file_name']) ?></div>
            <div class="file-meta">
                <span class="file-project-dot" style="background:<?= $f['project_color'] ?>"></span>
                <?= sanitize($f['project_name']) ?>
            </div>
            <div class="file-meta">
                <i class="fa-solid fa-list-check"></i>
                <a href="/pages/tasks.php?task_id=<?= $f['task_id'] ?>" style="color:var(--primary)">
                    <?= sanitize($f['task_name']) ?>
                </a>
            </div>
            <div class="file-meta file-meta-muted">
                <i class="fa-regular fa-user"></i> <?= sanitize($f['uploader_name']) ?>
                &nbsp;·&nbsp;
                <i class="fa-regular fa-clock"></i> <?= date('d.m.Y', strtotime($f['created_at'])) ?>
                <?php if ($size_kb): ?>&nbsp;·&nbsp; <?= $size_kb ?> KB<?php endif; ?>
            </div>
        </div>
        <a href="<?= sanitize($f['file_path']) ?>" class="file-download-btn" download title="Pobierz">
            <i class="fa-solid fa-download"></i>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
