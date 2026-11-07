<?php
// api/search.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/middleware.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$q = trim($_GET['q'] ?? '');

header('Content-Type: application/json; charset=utf-8');

if (strlen($q) < 2 || strlen($q) > 255) {
    echo json_encode(['results' => []]);
    die();
}

$like = '%' . $q . '%';
$results = [];

// Search Tasks
$stmt_tasks = $db->prepare("
    SELECT t.id, t.name as title, 'task' as _type
    FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND (t.name LIKE ? OR t.description LIKE ?) AND p.is_archived = 0
    LIMIT 5
");
$stmt_tasks->execute([$user_id, $user_id, $like, $like]);
$results = array_merge($results, $stmt_tasks->fetchAll());

// Search Projects
$stmt_projects = $db->prepare("
    SELECT p.id, p.name as title, 'project' as _type
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND (p.name LIKE ? OR p.description LIKE ?) AND p.is_archived = 0
    LIMIT 5
");
$stmt_projects->execute([$user_id, $user_id, $like, $like]);
$results = array_merge($results, $stmt_projects->fetchAll());

echo json_encode(['results' => $results]);
