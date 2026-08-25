<?php
// services/TaskService.php

class TaskService {
    public static function getUserTasks($db, $user_id, $filter_project = 0) {
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

        $stmt = $db->prepare($base_query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getProjects($db, $user_id) {
        $stmt = $db->prepare("
            SELECT DISTINCT p.id, p.name, p.color FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
            ORDER BY p.name ASC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }

    public static function getActiveUsers($db) {
        $stmt = $db->query("SELECT id, full_name, email FROM users WHERE status='Active' ORDER BY full_name ASC");
        return $stmt->fetchAll();
    }
}
