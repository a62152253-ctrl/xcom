<?php
$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

$stmt_projs = $db->prepare("
    SELECT DISTINCT p.id, p.name, p.color FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
    ORDER BY p.name ASC
");
$stmt_projs->execute([$user_id, $user_id]);
$user_projects = $stmt_projs->fetchAll();

$stmt_users = $db->query("SELECT id, full_name, email FROM users WHERE status='Active' ORDER BY full_name ASC");
$all_users = $stmt_users->fetchAll();

$open_task_id = (int)($_GET['task_id'] ?? 0);
$filter_project = (int)($_GET['project_id'] ?? 0);

$base_query = "
    SELECT t.*, p.name as project_name, p.color as project_color,
           u.full_name as assigned_name
    FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
";

$params = [$user_id, $user_id];

if ($filter_project) {
    $base_query .= " AND t.project_id = ?";
    $params[] = $filter_project;
}

$base_query .= " ORDER BY FIELD(t.priority,'Critical','High','Medium','Low'), t.deadline ASC";

$stmt_tasks = $db->prepare($base_query);
$stmt_tasks->execute($params);
$all_tasks = $stmt_tasks->fetchAll();

$open_task = null;
if ($open_task_id) {
    $stmt_ot = $db->prepare("SELECT t.*, p.name as project_name, p.color as project_color FROM tasks t INNER JOIN projects p ON t.project_id = p.id WHERE t.id = ? LIMIT 1");
    $stmt_ot->execute([$open_task_id]);
    $open_task = $stmt_ot->fetch();
}

$columns = ['To Do' => [], 'In Progress' => [], 'Review' => [], 'Done' => []];
foreach ($all_tasks as $t) {
    if (isset($columns[$t['status']])) $columns[$t['status']][] = $t;
}
