<?php
// models/Project.php
require_once __DIR__ . '/../config/database.php';

class Project {
    public static function getUserProjects($db, $user_id) {
        $stmt = $db->prepare("
            SELECT DISTINCT p.id, p.name, p.color, p.created_by,
                   pm_me.role as user_project_role,
                   (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count,
                   (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
                   (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status='Done') as done_tasks,
                   p.description, p.deadline
            FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            LEFT JOIN project_members pm_me ON p.id = pm_me.project_id AND pm_me.user_id = ?
            WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
            ORDER BY p.name ASC
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        return $stmt->fetchAll();
    }

    public static function getProjectById($db, $project_id) {
        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        return $stmt->fetch();
    }

    public static function createProject($db, $data) {
        $stmt = $db->prepare("INSERT INTO projects (name, description, color, deadline, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['color'],
            $data['deadline'],
            $data['created_by']
        ]);
        return $db->lastInsertId();
    }

    public static function updateProject($db, $project_id, $data) {
        $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, color = ?, deadline = ? WHERE id = ?");
        $stmt->execute([
            $data['name'],
            $data['description'],
            $data['color'],
            $data['deadline'],
            $project_id
        ]);
        return true;
    }

    public static function archiveProject($db, $project_id) {
        $stmt = $db->prepare("UPDATE projects SET is_archived = 1 WHERE id = ?");
        $stmt->execute([$project_id]);
        return true;
    }

    public static function addMember($db, $project_id, $user_id, $role) {
        $stmt = $db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
        $stmt->execute([$project_id, $user_id, $role]);
        return true;
    }
}
