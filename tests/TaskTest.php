<?php
// tests/TaskTest.php

class TaskTest {
    private $db;

    public function __construct() {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->setupSchema();
    }

    private function setupSchema() {
        $this->db->exec("
            CREATE TABLE tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER,
                name TEXT,
                description TEXT,
                status TEXT DEFAULT 'To Do',
                priority TEXT DEFAULT 'Medium',
                assigned_to INTEGER,
                created_by INTEGER
            )
        ");
    }

    public function run() {
        echo "Running TaskTest...\n";
        $this->testCreateTask();
        $this->testUpdateTaskStatus();
        $this->testDeleteTask();
        echo "TaskTest Passed!\n";
    }

    private function testCreateTask() {
        $stmt = $this->db->prepare("INSERT INTO tasks (project_id, name, description, priority, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([1, 'New Task', 'Task Desc', 'High', 1]);
        $taskId = $this->db->lastInsertId();

        if (!$taskId) {
            throw new Exception("Task creation failed.");
        }

        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();

        if ($task['name'] !== 'New Task') {
            throw new Exception("Task data mismatch.");
        }
    }

    private function testUpdateTaskStatus() {
        $stmt = $this->db->prepare("INSERT INTO tasks (project_id, name, created_by) VALUES (?, ?, ?)");
        $stmt->execute([1, 'Status Task', 1]);
        $taskId = $this->db->lastInsertId();

        $stmt = $this->db->prepare("UPDATE tasks SET status = 'Done' WHERE id = ?");
        $stmt->execute([$taskId]);

        $stmt = $this->db->prepare("SELECT status FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $status = $stmt->fetchColumn();

        if ($status !== 'Done') {
            throw new Exception("Task status update failed.");
        }
    }

    private function testDeleteTask() {
        $stmt = $this->db->prepare("INSERT INTO tasks (project_id, name, created_by) VALUES (?, ?, ?)");
        $stmt->execute([1, 'Delete Task', 1]);
        $taskId = $this->db->lastInsertId();

        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);

        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();

        if ($task) {
            throw new Exception("Task deletion failed.");
        }
    }
}

$test = new TaskTest();
$test->run();
