<?php

namespace Services;

class TaskService {
    public static function getUserTasks($db, $user_id, $filter_project = 0, $sort_sql = '') {
        $params = [$user_id, $user_id];

        $base_query = "
            SELECT t.*, p.name as project_name, p.color as project_color,
                   u.full_name as assigned_name
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
        ";

        if ($filter_project > 0) {
            $base_query .= " AND t.project_id = ?";
            $params[] = $filter_project;
        }

        if ($sort_sql) {
            $base_query .= " ORDER BY " . $sort_sql;
        }

        $stmt = $db->prepare($base_query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
