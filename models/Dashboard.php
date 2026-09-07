<?php
class Dashboard {
    public static function getProjectsCount($db, $user_id) {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT p.id) FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0");
        $stmt->execute([$user_id, $user_id]);
        return (int)$stmt->fetchColumn();
    }

    public static function getActiveTasksCount($db, $user_id) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND t.status != 'Done' AND p.is_archived = 0");
        $stmt->execute([$user_id, $user_id]);
        return (int)$stmt->fetchColumn();
    }

    public static function getTasksToday($db, $user_id) {
        $stmt = $db->prepare("SELECT t.*, p.name as project_name, p.color FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND t.deadline = CURDATE() AND t.status != 'Done' AND p.is_archived = 0");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }

    public static function getOverdueCount($db, $user_id) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND t.deadline < CURDATE() AND t.status != 'Done' AND p.is_archived = 0");
        $stmt->execute([$user_id, $user_id]);
        return (int)$stmt->fetchColumn();
    }

    public static function getDoneCount($db, $user_id) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND t.status = 'Done' AND p.is_archived = 0");
        $stmt->execute([$user_id, $user_id]);
        return (int)$stmt->fetchColumn();
    }

    public static function getPrioritiesData($db, $user_id) {
        $stmt = $db->prepare("SELECT t.priority, COUNT(*) as qty FROM tasks t INNER JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON p.id = pm.project_id WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0 GROUP BY t.priority");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }

    public static function getActivityLogs($db) {
        $stmt = $db->prepare("SELECT l.*, u.full_name FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 10");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getTopProjects($db, $user_id) {
        $stmt = $db->prepare("
            SELECT DISTINCT p.id, p.name, p.color,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status='Done') as done,
                (SELECT COUNT(DISTINCT user_id) FROM project_members WHERE project_id = p.id) as member_count
            FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
            LIMIT 5
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }
}
