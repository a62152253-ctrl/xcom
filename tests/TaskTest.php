<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../services/TaskService.php';

class TaskTest {
    public static function run() {
        echo "Running TaskTest...\n";

        $db = Database::getInstance()->getConnection();

        // Setup test data
        $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, role, status) VALUES (?, ?, ?, 'Owner', 'Active')");
        $stmt->execute(['tasktest_' . time() . '@example.com', 'hash', 'Test User']);
        $user_id = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO projects (name, created_by) VALUES (?, ?)");
        $stmt->execute(['Test Project', $user_id]);
        $project_id = $db->lastInsertId();

        // Test task creation
        $task_id = Task::createTask($db, [
            'project_id' => $project_id,
            'name' => 'Test Task',
            'description' => 'Test Description',
            'deadline' => null,
            'priority' => 'High',
            'assigned_to' => $user_id,
            'created_by' => $user_id
        ]);

        if ($task_id) {
            echo "✅ Task creation passed\n";
        } else {
            echo "❌ Task creation failed\n";
        }

        // Test fetching task
        $task = Task::getTaskById($db, $task_id);
        if ($task && $task['name'] === 'Test Task') {
            echo "✅ Task fetching passed\n";
        } else {
            echo "❌ Task fetching failed\n";
        }

        // Test updating task status
        $updated = Task::updateTaskStatus($db, $task_id, 'In Progress');
        if ($updated) {
            $task = Task::getTaskById($db, $task_id);
            if ($task['status'] === 'In Progress') {
                echo "✅ Task status update passed\n";
            } else {
                echo "❌ Task status update failed\n";
            }
        } else {
            echo "❌ Task status update failed\n";
        }

        // Test task deletion
        $deleted = Task::deleteTask($db, $task_id);
        if ($deleted) {
            $task = Task::getTaskById($db, $task_id);
            if (!$task) {
                echo "✅ Task deletion passed\n";
            } else {
                echo "❌ Task deletion failed\n";
            }
        } else {
            echo "❌ Task deletion failed\n";
        }

        // Cleanup
        $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$project_id]);
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
    }
}

TaskTest::run();
