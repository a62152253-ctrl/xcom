<?php

function getDashboardData($db, $user_id) {
    // Stats
    $stmt_projects = $db->prepare("SELECT COUNT(DISTINCT p.id) FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0");
    $stmt_projects->execute([$user_id, $user_id]);
    $projects_count = (int)$stmt_projects->fetchColumn();

    $stmt_tasks = $db->prepare("SELECT COUNT(*) FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND t.status != 'Done' AND p.is_archived = 0");
    $stmt_tasks->execute([$user_id, $user_id]);
    $active_tasks_count = (int)$stmt_tasks->fetchColumn();

    $stmt_today = $db->prepare("SELECT t.*, p.name as project_name, p.color FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND t.deadline = CURDATE() AND t.status != 'Done' AND p.is_archived = 0");
    $stmt_today->execute([$user_id, $user_id]);
    $tasks_today = $stmt_today->fetchAll();

    $stmt_overdue = $db->prepare("SELECT COUNT(*) FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND t.deadline < CURDATE() AND t.status != 'Done' AND p.is_archived = 0");
    $stmt_overdue->execute([$user_id, $user_id]);
    $overdue_count = (int)$stmt_overdue->fetchColumn();

    $stmt_done = $db->prepare("SELECT COUNT(*) FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND t.status = 'Done' AND p.is_archived = 0");
    $stmt_done->execute([$user_id, $user_id]);
    $done_count = (int)$stmt_done->fetchColumn();

    $stmt_pri = $db->prepare("SELECT t.priority, COUNT(*) as qty FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0 GROUP BY t.priority");
    $stmt_pri->execute([$user_id, $user_id]);
    $priorities_data = $stmt_pri->fetchAll();
    $priorities_json = ['Low' => 0, 'Medium' => 0, 'High' => 0, 'Critical' => 0];
    foreach ($priorities_data as $pd) if (isset($priorities_json[$pd['priority']])) $priorities_json[$pd['priority']] = (int)$pd['qty'];

    $stmt_logs = $db->prepare("SELECT l.*, u.full_name FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 10");
    $stmt_logs->execute();
    $activity_logs = $stmt_logs->fetchAll();

    $stmt_top_proj = $db->prepare("
        SELECT DISTINCT p.id, p.name, p.color,
            (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total,
            (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status='Done') as done,
            (SELECT COUNT(DISTINCT user_id) FROM project_members WHERE project_id = p.id) as member_count
        FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id
        WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
        LIMIT 5
    ");
    $stmt_top_proj->execute([$user_id, $user_id]);
    $top_projects = $stmt_top_proj->fetchAll();

    $all_tasks_total = (int)$db->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
    $all_tasks_done  = (int)$db->query("SELECT COUNT(*) FROM tasks WHERE status='Done'")->fetchColumn();
    $ws_pct = $all_tasks_total > 0 ? round($all_tasks_done / $all_tasks_total * 100) : 0;

    return [
        'projects_count' => $projects_count,
        'active_tasks_count' => $active_tasks_count,
        'tasks_today' => $tasks_today,
        'overdue_count' => $overdue_count,
        'done_count' => $done_count,
        'priorities_json' => $priorities_json,
        'activity_logs' => $activity_logs,
        'top_projects' => $top_projects,
        'ws_pct' => $ws_pct,
    ];
}
