<?php
// models/Project.php

class Project {
    public static function getAll($db, $user_id) {
        $stmt = $db->prepare("
            SELECT DISTINCT p.*, u.full_name as creator_name
            FROM projects p
            LEFT JOIN project_members pm ON p.id = pm.project_id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.created_by = ? OR pm.user_id = ?
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }

    public static function getById($db, $project_id) {
        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        return $stmt->fetch();
    }

    public static function create($db, $name, $description, $color, $deadline, $user_id) {
        $stmt = $db->prepare("INSERT INTO projects (name, description, color, deadline, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $color, $deadline ?: null, $user_id]);
        $project_id = $db->lastInsertId();

        // Add creator as Project Owner
        $stmt_member = $db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'Owner')");
        $stmt_member->execute([$project_id, $user_id]);

        return $project_id;
    }

    public static function update($db, $id, $name, $description, $color, $deadline) {
        $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, color = ?, deadline = ? WHERE id = ?");
        return $stmt->execute([$name, $description, $color, $deadline ?: null, $id]);
    }

    public static function archive($db, $id) {
        $stmt = $db->prepare("UPDATE projects SET is_archived = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function restore($db, $id) {
        $stmt = $db->prepare("UPDATE projects SET is_archived = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function delete($db, $id) {
        $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function getArchivedCreator($db, $id) {
        $stmt = $db->prepare("SELECT created_by FROM projects WHERE id = ? AND is_archived = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function findUserByEmail($db, $email) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function addMember($db, $project_id, $user_id, $role) {
        $stmt = $db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
        return $stmt->execute([$project_id, $user_id, $role]);
    }
}
