<?php
// tests/ProjectTest.php
require_once __DIR__ . '/../models/Project.php';

class ProjectTest {
    private $db;

    public function __construct() {
        // Mock PDO Database
        $this->db = new class extends PDO {
            public function __construct() {}
            public function prepare($query, $options = []) {
                return new class {
                    public function execute($params = null) { return true; }
                    public function fetchAll($mode = null) { return [['id' => 1, 'name' => 'Mock Project']]; }
                    public function fetch($mode = null) { return ['id' => 1, 'name' => 'Mock Project']; }
                    public function fetchColumn($column_number = 0) { return 0; }
                };
            }
            public function lastInsertId($name = null) { return "5"; }
        };
    }

    public function run() {
        echo "Running ProjectTest...\n";

        $this->testGetUserProjects();
        $this->testCreateProject();

        echo "ProjectTest passed.\n";
    }

    private function testGetUserProjects() {
        $projects = Project::getUserProjects($this->db, 1);
        if (empty($projects) || $projects[0]['name'] !== 'Mock Project') {
            throw new Exception("testGetUserProjects failed");
        }
    }

    private function testCreateProject() {
        $data = [
            'name' => 'New Proj',
            'description' => 'Desc',
            'color' => '#fff',
            'deadline' => '2023-12-31',
            'created_by' => 1
        ];
        $id = Project::createProject($this->db, $data);
        if ($id !== "5") {
            throw new Exception("testCreateProject failed");
        }
    }
}

$test = new ProjectTest();
$test->run();
