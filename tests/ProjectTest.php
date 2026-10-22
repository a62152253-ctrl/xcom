<?php
// tests/ProjectTest.php

class ProjectTest {
    private $db;

    public function __construct() {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->setupSchema();
    }

    private function setupSchema() {
        $this->db->exec("
            CREATE TABLE projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT,
                description TEXT,
                color TEXT,
                created_by INTEGER,
                is_archived INTEGER DEFAULT 0
            )
        ");

        $this->db->exec("
            CREATE TABLE project_members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER,
                user_id INTEGER,
                role TEXT
            )
        ");
    }

    public function run() {
        echo "Running ProjectTest...\n";
        $this->testCreateProject();
        $this->testProjectMembership();
        echo "ProjectTest Passed!\n";
    }

    private function testCreateProject() {
        $stmt = $this->db->prepare("INSERT INTO projects (name, description, color, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute(['New Project', 'Desc', '#000000', 1]);
        $projectId = $this->db->lastInsertId();

        if (!$projectId) {
            throw new Exception("Project creation failed.");
        }

        $stmt = $this->db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'Owner')");
        $stmt->execute([$projectId, 1]);
    }

    private function testProjectMembership() {
        $stmt = $this->db->prepare("INSERT INTO projects (name, created_by) VALUES (?, ?)");
        $stmt->execute(['Collab Project', 1]);
        $projectId = $this->db->lastInsertId();

        $stmt = $this->db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'Member')");
        $stmt->execute([$projectId, 2]); // User 2 is Member

        $stmt = $this->db->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$projectId, 2]);
        $role = $stmt->fetchColumn();

        if ($role !== 'Member') {
            throw new Exception("Project membership retrieval failed.");
        }
    }
}

$test = new ProjectTest();
$test->run();
