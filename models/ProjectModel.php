<?php
// models/ProjectModel.php
require_once __DIR__ . '/../config/database.php';

class ProjectModel {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllProjects($user_id) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.*, u.full_name as creator_name
            FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.created_by = ? OR pm.user_id = ?
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }

    public function getProjectsWithStats($user_id, $sort_sql) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.*, u.full_name as creator_name,
                pm_me.role as user_project_role,
                (SELECT COUNT(DISTINCT user_id) FROM project_members WHERE project_id = p.id) as member_count,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as total_tasks,
                (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status='Done') as done_tasks
            FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            LEFT JOIN project_members pm_me ON p.id = pm_me.project_id AND pm_me.user_id = ?
            LEFT JOIN users u ON p.created_by = u.id
            WHERE (p.created_by = ? OR pm.user_id = ?) AND p.is_archived = 0
            ORDER BY $sort_sql
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        return $stmt->fetchAll();
    }

    public function createProject($name, $description, $color, $deadline, $user_id) {
        $stmt = $this->db->prepare("INSERT INTO projects (name, description, color, deadline, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $color, $deadline, $user_id]);
        return $this->db->lastInsertId();
    }

    public function addProjectOwner($project_id, $user_id) {
        $stmt = $this->db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'Owner')");
        return $stmt->execute([$project_id, $user_id]);
    }

    public function updateProject($id, $name, $description, $color, $deadline) {
        $stmt = $this->db->prepare("UPDATE projects SET name = ?, description = ?, color = ?, deadline = ? WHERE id = ?");
        return $stmt->execute([$name, $description, $color, $deadline, $id]);
    }

    public function archiveProject($id) {
        $stmt = $this->db->prepare("UPDATE projects SET is_archived = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function restoreProject($id) {
        $stmt = $this->db->prepare("UPDATE projects SET is_archived = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getProjectCreator($id) {
        $stmt = $this->db->prepare("SELECT created_by FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getArchivedProjectCreator($id) {
        $stmt = $this->db->prepare("SELECT created_by FROM projects WHERE id = ? AND is_archived = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function deleteProject($id) {
        $stmt = $this->db->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getProjectName($id) {
        $stmt = $this->db->prepare("SELECT name FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }

    public function findUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function addProjectMember($project_id, $user_id, $role) {
        $stmt = $this->db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
        return $stmt->execute([$project_id, $user_id, $role]);
    }

    public function beginTransaction() {
        return $this->db->beginTransaction();
    }

    public function commit() {
        return $this->db->commit();
    }

    public function rollBack() {
        return $this->db->rollBack();
    }
}
