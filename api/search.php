<?php
// api/search.php - API for Global Search (Command Palette)
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2 || strlen($q) > 255) {
    echo json_encode(['results' => []]);
    exit;
}

$like = '%' . $q . '%';
$results = [];

// Search Tasks
$stmt = $db->prepare("
    SELECT t.id, t.name as title, p.name as project_name
    FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND (t.name LIKE ? OR t.description LIKE ?) AND p.is_archived = 0
    ORDER BY t.updated_at DESC LIMIT 5
");
$stmt->execute([$user_id, $user_id, $like, $like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = [
        'id' => $r['id'],
        'title' => htmlspecialchars($r['title']),
        'subtitle' => 'Zadanie • ' . htmlspecialchars($r['project_name']),
        'type' => 'task'
    ];
}

// Search Projects
$stmt = $db->prepare("
    SELECT DISTINCT p.id, p.name as title
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND (p.name LIKE ? OR p.description LIKE ?) AND p.is_archived = 0
    LIMIT 3
");
$stmt->execute([$user_id, $user_id, $like, $like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = [
        'id' => $r['id'],
        'title' => htmlspecialchars($r['title']),
        'subtitle' => 'Projekt',
        'type' => 'project'
    ];
}

// Search Notes
$stmt = $db->prepare("
    SELECT id, title
    FROM notes
    WHERE user_id = ? AND (title LIKE ? OR content LIKE ?)
    LIMIT 3
");
$stmt->execute([$user_id, $like, $like]);
foreach ($stmt->fetchAll() as $r) {
    $results[] = [
        'id' => $r['id'],
        'title' => htmlspecialchars($r['title']),
        'subtitle' => 'Notatka',
        'type' => 'note'
    ];
}

echo json_encode(['results' => $results]);
