<?php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../config/database.php';

class TaskTest {
    public static function run() {
        echo "Running TaskTest...\n";

        // Mock database connection
        $db = new class extends PDO {
            public function __construct() {}
            public function prepare($query, $options = []) {
                return new class {
                    public function execute($params = null) { return true; }
                    public function fetchAll() { return [['id' => 1, 'title' => 'Test task']]; }
                    public function fetch() { return ['id' => 1, 'title' => 'Test task']; }
                };
            }
            public function lastInsertId($name = null) { return 1; }
        };

        $tasks = Task::getAllByUser($db, 1);
        if (count($tasks) === 1 && $tasks[0]['title'] === 'Test task') {
            echo "✅ TaskTest: Read task passed\n";
            return true;
        } else {
            echo "❌ TaskTest: Read task failed\n";
            return false;
        }
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    TaskTest::run();
}
