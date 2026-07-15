<?php
// api/stats.php — Live stats for dashboard widgets
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/middleware.php';
require_login();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Tasks by status
$stmt = $db->prepare("
    SELECT t.status, COUNT(*) as cnt
    FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
    GROUP BY t.status
");
$stmt->execute([$user_id, $user_id]);
$status_rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$statuses = ['To Do' => 0, 'In Progress' => 0, 'Review' => 0, 'Done' => 0];
foreach ($status_rows as $k => $v) if (isset($statuses[$k])) $statuses[$k] = (int)$v;

// Projects count
$stmt2 = $db->prepare("
    SELECT COUNT(DISTINCT p.id) FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
");
$stmt2->execute([$user_id, $user_id]);
$projects_count = (int)$stmt2->fetchColumn();

// Overdue
$stmt3 = $db->prepare("
    SELECT COUNT(*) FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND t.deadline < CURDATE() AND t.status != 'Done' AND p.is_archived = 0
");
$stmt3->execute([$user_id, $user_id]);
$overdue = (int)$stmt3->fetchColumn();

// Today tasks
$stmt4 = $db->prepare("
    SELECT COUNT(*) FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND t.deadline = CURDATE() AND t.status != 'Done' AND p.is_archived = 0
");
$stmt4->execute([$user_id, $user_id]);
$today_count = (int)$stmt4->fetchColumn();

// Unread notifications
$stmt5 = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt5->execute([$user_id]);
$unread_notifs = (int)$stmt5->fetchColumn();

// Last 14 days completed
$stmt6 = $db->prepare("
    SELECT DATE(updated_at) as day, COUNT(*) as cnt
    FROM tasks t
    INNER JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE (p.created_by = ? OR pm.user_id = ?) AND t.status = 'Done'
      AND t.updated_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(updated_at)
");
$stmt6->execute([$user_id, $user_id]);
$daily_raw = $stmt6->fetchAll(PDO::FETCH_KEY_PAIR);

$trend = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $trend[] = ['day' => date('D', strtotime($day)), 'count' => $daily_raw[$day] ?? 0];
}

echo json_encode([
    'statuses'       => $statuses,
    'projects_count' => $projects_count,
    'overdue'        => $overdue,
    'today_count'    => $today_count,
    'unread_notifs'  => $unread_notifs,
    'trend'          => $trend,
    'total_active'   => array_sum($statuses) - $statuses['Done'],
    'total_done'     => $statuses['Done'],
]);
