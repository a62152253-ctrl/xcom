<?php
// models/Task.php
class Task {
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

        // Use CASE instead of FIELD for cross-database compatibility (MySQL/SQLite)
        $base_query .= " ORDER BY
            CASE t.priority
                WHEN 'Critical' THEN 1
                WHEN 'High' THEN 2
                WHEN 'Medium' THEN 3
                WHEN 'Low' THEN 4
                ELSE 5
            END, t.deadline ASC";

        $stmt = $db->prepare($base_query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
