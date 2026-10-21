<?php
require_once __DIR__ . '/../includes/middleware.php';
require_auth_api();
$q = trim($_GET['q'] ?? '');

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];
$results = [];

if (strlen($q) >= 2 && strlen($q) <= 255) {
    $like = '%' . $q . '%';

    // Secure task search
    $stmt = $db->prepare("
        SELECT t.id, t.name as title, 'task' as _type
        FROM tasks t
        INNER JOIN projects p ON t.project_id = p.id
        LEFT JOIN project_members pm ON p.id = pm.project_id
        WHERE (p.created_by = ? OR pm.user_id = ?) AND (t.name LIKE ? OR t.description LIKE ?) AND p.is_archived = 0
        LIMIT 5
    ");
    $stmt->execute([$user_id, $user_id, $like, $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $results[] = $r;

    // Secure project search
    $stmt = $db->prepare("
        SELECT DISTINCT p.id, p.name as title, 'project' as _type
        FROM projects p
        LEFT JOIN project_members pm ON p.id = pm.project_id
        WHERE (p.created_by = ? OR pm.user_id = ?) AND (p.name LIKE ? OR p.description LIKE ?) AND p.is_archived = 0
        LIMIT 5
    ");
    $stmt->execute([$user_id, $user_id, $like, $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $results[] = $r;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['results' => $results]);
