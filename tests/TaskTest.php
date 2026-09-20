<?php
// tests/TaskTest.php
require_once __DIR__ . '/../models/Task.php';

class TaskTest {
    public function run() {
        echo "Running TaskTest...\n";

        // Check if Task class exists
        if (class_exists('Task')) {
            echo "✅ Task model exists.\n";
        } else {
            echo "❌ Task model missing.\n";
            return false;
        }

        // Cannot test database methods directly without full env setup in this scope, but verifying structure
        if (method_exists('Task', 'getTasksByUser')) {
            echo "✅ Task::getTasksByUser method exists.\n";
        } else {
            echo "❌ Task::getTasksByUser method missing.\n";
            return false;
        }

        echo "✅ TaskTest passed.\n";
        return true;
    }
}

$test = new TaskTest();
$test->run();
