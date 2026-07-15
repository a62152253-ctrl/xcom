<?php
// api/search.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/middleware.php';
require_login();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$q = trim($_GET['q'] ?? '');
$limit = min((int)($_GET['limit'] ?? 10), 30);

if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$like = '%' . $q . '%';
$results = [];

// Search tasks
$stmt_tasks = $db->prepare("
    SELECT t.id, t.name as title, t.status, t.priority, p.name as project_name
    FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND (t.name LIKE ? OR t.description LIKE ?) AND p.is_archived = 0
    LIMIT ?
");
$stmt_tasks->execute([$user_id, $user_id, $like, $like, (int)($limit * 0.6)]);
foreach ($stmt_tasks->fetchAll() as $r) {
    $results[] = [
        'type'     => 'task',
        'id'       => $r['id'],
        'title'    => $r['title'],
        'subtitle' => $r['project_name'] . ' · ' . $r['status'],
        'priority' => $r['priority'],
        'url'      => '/pages/tasks.php?task_id=' . $r['id'],
    ];
}

// Search projects
$stmt_proj = $db->prepare("
    SELECT DISTINCT p.id, p.name as title, p.color
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND (p.name LIKE ? OR p.description LIKE ?) AND p.is_archived = 0
    LIMIT ?
");
$stmt_proj->execute([$user_id, $user_id, $like, $like, (int)($limit * 0.4)]);
foreach ($stmt_proj->fetchAll() as $r) {
    $results[] = [
        'type'     => 'project',
        'id'       => $r['id'],
        'title'    => $r['title'],
        'subtitle' => 'Projekt',
        'color'    => $r['color'],
        'url'      => '/pages/tasks.php?project_id=' . $r['id'],
    ];
}

// Search notes (personal)
$stmt_notes = $db->prepare("SELECT id, title FROM notes WHERE user_id = ? AND (title LIKE ? OR content LIKE ?) LIMIT 3");
$stmt_notes->execute([$user_id, $like, $like]);
foreach ($stmt_notes->fetchAll() as $r) {
    $results[] = [
        'type'     => 'note',
        'id'       => $r['id'],
        'title'    => $r['title'],
        'subtitle' => 'Notatka',
        'url'      => '/pages/notes.php',
    ];
}

echo json_encode([
    'query'   => $q,
    'count'   => count($results),
    'results' => array_slice($results, 0, $limit),
]);
