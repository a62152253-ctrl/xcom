<?php
namespace Services;

use PDO;

class TaskService {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Gets all tasks accessible to a user, optionally filtered by project.
     * @return array Array of Task objects (or arrays for easy frontend compatibility)
     */
    public function getUserTasks(int $userId, int $projectId = 0): array {
        $baseQuery = "
            SELECT t.*, p.name as project_name, p.color as project_color,
                   u.full_name as assigned_name
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            LEFT JOIN project_members pm ON p.id = pm.project_id
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
        ";

        $params = [$userId, $userId];

        if ($projectId > 0) {
            $baseQuery .= " AND t.project_id = ?";
            $params[] = $projectId;
        }

        $baseQuery .= " ORDER BY FIELD(t.priority,'Critical','High','Medium','Low'), t.deadline ASC";

        $stmt = $this->db->prepare($baseQuery);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tasks = [];
        foreach ($results as $row) {
            $tasks[] = $row;
        }
        return $tasks;
    }

    public function getTaskById(int $taskId): ?array {
        $stmt = $this->db->prepare("
            SELECT t.*, p.name as project_name, p.color as project_color
            FROM tasks t
            INNER JOIN projects p ON t.project_id = p.id
            WHERE t.id = ? LIMIT 1
        ");
        $stmt->execute([$taskId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}
