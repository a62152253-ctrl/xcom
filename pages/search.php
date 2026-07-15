<?php
// pages/search.php — Global search results page
require_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$q = trim($_GET['q'] ?? '');

// Whitelist validation for type filter
$type_filter = $_GET['type'] ?? 'all';
if (!in_array($type_filter, ['all', 'task', 'project', 'note'], true)) {
    $type_filter = 'all';
}

$results = [];
$total = 0;

if (strlen($q) >= 2 && strlen($q) <= 255) {
    $like = '%' . $q . '%';

    if ($type_filter === 'all' || $type_filter === 'task') {
        $stmt = $db->prepare("
            SELECT t.id, t.name as title, t.status, t.priority, t.deadline, t.description,
                   p.name as project_name, p.color as project_color
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?) AND (t.name LIKE ? OR t.description LIKE ?) AND p.is_archived = 0
            ORDER BY t.updated_at DESC LIMIT 30
        ");
        $stmt->execute([$user_id, $user_id, $like, $like]);
        foreach ($stmt->fetchAll() as $r) $results[] = array_merge($r, ['_type' => 'task']);
    }

    if ($type_filter === 'all' || $type_filter === 'project') {
        $stmt = $db->prepare("
            SELECT DISTINCT p.id, p.name as title, p.description, p.color as project_color, p.deadline, p.created_at
            FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?) AND (p.name LIKE ? OR p.description LIKE ?) AND p.is_archived = 0
            LIMIT 20
        ");
        $stmt->execute([$user_id, $user_id, $like, $like]);
        foreach ($stmt->fetchAll() as $r) $results[] = array_merge($r, ['_type' => 'project']);
    }

    if ($type_filter === 'all' || $type_filter === 'note') {
        $stmt = $db->prepare("SELECT *, 'note' as _type FROM notes WHERE user_id = ? AND (title LIKE ? OR content LIKE ?) LIMIT 15");
        $stmt->execute([$user_id, $like, $like]);
        foreach ($stmt->fetchAll() as $r) $results[] = $r;
    }

    $total = count($results);
}

// Highlight search terms in text - sanitize properly
function highlight($text, $q) {
    if (empty($q)) return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $q_escaped = htmlspecialchars($q, ENT_QUOTES, 'UTF-8');
    return preg_replace('/(' . preg_quote($q_escaped, '/') . ')/i', '<mark>$1</mark>', htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-magnifying-glass"></i> Wyniki wyszukiwania</h1>
        <p class="page-subtitle">
            <?php if ($q): ?>
                <?= $total ?> wyniki dla <strong><?= htmlspecialchars($q, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
            <?php else: ?>
                Wpisz frazę w pasku wyszukiwania powyżej.
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if ($q): ?>
<!-- Search form -->
<div style="margin-bottom:1.5rem;max-width:600px">
    <form method="GET" style="display:flex;gap:.75rem">
        <div style="position:relative;flex:1">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
            <input name="q" value="<?= htmlspecialchars($q, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="form-control" style="padding-left:2.5rem" placeholder="Szukaj..." maxlength="255">
        </div>
        <button class="btn btn-primary" type="submit" style="width:auto">Szukaj</button>
    </form>
</div>

<!-- Type filter -->
<div class="filter-tabs" style="margin-bottom:1.5rem">
    <a href="?q=<?= htmlspecialchars(urlencode($q), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>&type=all"     class="filter-tab <?= $type_filter === 'all'     ? 'active' : '' ?>">Wszystko (<?= $total ?>)</a>
    <a href="?q=<?= htmlspecialchars(urlencode($q), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>&type=task"    class="filter-tab <?= $type_filter === 'task'    ? 'active' : '' ?>"><i class="fa-solid fa-list-check"></i> Zadania</a>
    <a href="?q=<?= htmlspecialchars(urlencode($q), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>&type=project" class="filter-tab <?= $type_filter === 'project' ? 'active' : '' ?>"><i class="fa-solid fa-folder"></i> Projekty</a>
    <a href="?q=<?= htmlspecialchars(urlencode($q), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>&type=note"    class="filter-tab <?= $type_filter === 'note'    ? 'active' : '' ?>"><i class="fa-solid fa-note-sticky"></i> Notatki</a>
</div>

<?php if (empty($results)): ?>
<div class="empty-state">
    <i class="fa-solid fa-magnifying-glass"></i>
    <h3>Brak wyników</h3>
    <p>Spróbuj innych słów kluczowych lub zmień filtr.</p>
</div>
<?php else: ?>
<div class="search-results-list">
    <?php foreach ($results as $r): ?>
    <?php if ($r['_type'] === 'task'): ?>
    <a href="/pages/tasks.php?task_id=<?= (int)$r['id'] ?>" class="search-result-card">
        <div class="src-icon src-icon--task"><i class="fa-solid fa-list-check"></i></div>
        <div class="src-body">
            <div class="src-title"><?= highlight($r['title'], $q) ?></div>
            <?php if ($r['description']): ?>
            <div class="src-desc"><?= highlight(mb_substr(strip_tags($r['description']), 0, 120), $q) ?>...</div>
            <?php endif; ?>
            <div class="src-meta">
                <span class="src-badge" style="background:<?= htmlspecialchars($r['project_color'], ENT_QUOTES, 'UTF-8') ?>20;color:<?= htmlspecialchars($r['project_color'], ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-solid fa-folder"></i> <?= sanitize($r['project_name']) ?>
                </span>
                <span class="kanban-card-tag tag-<?= strtolower($r['priority']) ?>"><?= sanitize($r['priority']) ?></span>
                <span class="src-status"><?= sanitize($r['status']) ?></span>
                <?php if ($r['deadline']): ?>
                <span class="src-deadline"><i class="fa-regular fa-clock"></i> <?= date('d.m.Y', strtotime($r['deadline'])) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <i class="fa-solid fa-chevron-right src-arrow"></i>
    </a>

    <?php elseif ($r['_type'] === 'project'): ?>
    <a href="/pages/tasks.php?project_id=<?= (int)$r['id'] ?>" class="search-result-card">
        <div class="src-icon src-icon--project" style="background:<?= htmlspecialchars($r['project_color'], ENT_QUOTES, 'UTF-8') ?>20;color:<?= htmlspecialchars($r['project_color'], ENT_QUOTES, 'UTF-8') ?>">
            <i class="fa-solid fa-folder-open"></i>
        </div>
        <div class="src-body">
            <div class="src-title"><?= highlight($r['title'], $q) ?></div>
            <?php if ($r['description']): ?>
            <div class="src-desc"><?= highlight(mb_substr(strip_tags($r['description']), 0, 120), $q) ?>...</div>
            <?php endif; ?>
            <div class="src-meta">
                <span class="src-badge" style="color:var(--text-muted)"><i class="fa-solid fa-layer-group"></i> Projekt</span>
                <?php if ($r['deadline']): ?>
                <span class="src-deadline"><i class="fa-regular fa-clock"></i> Termin: <?= date('d.m.Y', strtotime($r['deadline'])) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <i class="fa-solid fa-chevron-right src-arrow"></i>
    </a>

    <?php elseif ($r['_type'] === 'note'): ?>
    <a href="/pages/notes.php" class="search-result-card">
        <div class="src-icon" style="background:<?= htmlspecialchars($r['color'] ?? '#3b82f6', ENT_QUOTES, 'UTF-8') ?>20;color:<?= htmlspecialchars($r['color'] ?? '#3b82f6', ENT_QUOTES, 'UTF-8') ?>">
            <i class="fa-solid fa-note-sticky"></i>
        </div>
        <div class="src-body">
            <div class="src-title"><?= highlight($r['title'], $q) ?></div>
            <?php if ($r['content']): ?>
            <div class="src-desc"><?= highlight(mb_substr(strip_tags($r['content']), 0, 120), $q) ?>...</div>
            <?php endif; ?>
            <div class="src-meta">
                <span class="src-badge"><i class="fa-solid fa-note-sticky"></i> Notatka</span>
                <span class="src-deadline"><i class="fa-regular fa-clock"></i> <?= date('d.m.Y', strtotime($r['updated_at'])) ?></span>
            </div>
        </div>
        <i class="fa-solid fa-chevron-right src-arrow"></i>
    </a>
    <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
