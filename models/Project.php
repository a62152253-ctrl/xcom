<?php
// models/Project.php

class Project {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listAccessible($user_id) {
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

    public function create($name, $description, $color, $deadline, $user_id) {
        $stmt = $this->db->prepare("INSERT INTO projects (name, description, color, deadline, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $color, $deadline ?: null, $user_id]);
        return $this->db->lastInsertId();
    }

    public function addMember($project_id, $user_id, $role) {
        $stmt = $this->db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
        return $stmt->execute([$project_id, $user_id, $role]);
    }

    public function edit($id, $name, $description, $color, $deadline) {
        $stmt = $this->db->prepare("UPDATE projects SET name = ?, description = ?, color = ?, deadline = ? WHERE id = ?");
        return $stmt->execute([$name, $description, $color, $deadline ?: null, $id]);
    }

    public function archive($id) {
        $stmt = $this->db->prepare("UPDATE projects SET is_archived = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function restore($id) {
        $stmt = $this->db->prepare("UPDATE projects SET is_archived = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getProjectCreator($id) {
        $stmt = $this->db->prepare("SELECT created_by FROM projects WHERE id = ? AND is_archived = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getProjectName($id) {
        $stmt = $this->db->prepare("SELECT name FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }

    public function getUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}
