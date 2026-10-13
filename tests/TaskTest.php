<?php
// tests/TaskTest.php
require_once __DIR__ . '/../models/Task.php';

class TaskTest {
    private $db;

    public function __construct() {
        // Mock PDO Database
        $this->db = new class extends PDO {
            public function __construct() {}
            public function prepare($query, $options = []) {
                return new class {
                    public function execute($params = null) { return true; }
                    public function fetchAll($mode = null) { return [['id' => 1, 'name' => 'Mock Task', 'project_name' => 'Mock Project']]; }
                    public function fetch($mode = null) { return ['id' => 1, 'name' => 'Mock Task', 'project_name' => 'Mock Project']; }
                    public function fetchColumn($column_number = 0) { return 0; }
                };
            }
            public function lastInsertId($name = null) { return "10"; }
        };
    }

    public function run() {
        echo "Running TaskTest...\n";

        $this->testGetUserTasks();
        $this->testCreateTask();

        echo "TaskTest passed.\n";
    }

    private function testGetUserTasks() {
        $tasks = Task::getUserTasks($this->db, 1, null);
        if (empty($tasks) || $tasks[0]['name'] !== 'Mock Task') {
            throw new Exception("testGetUserTasks failed");
        }
    }

    private function testCreateTask() {
        $data = [
            'project_id' => 1,
            'name' => 'New Task',
            'description' => 'Desc',
            'deadline' => '2023-12-31',
            'priority' => 'High',
            'assigned_to' => 2,
            'created_by' => 1
        ];
        $id = Task::createTask($this->db, $data);
        if ($id !== "10") {
            throw new Exception("testCreateTask failed");
        }
    }
}

$test = new TaskTest();
$test->run();
