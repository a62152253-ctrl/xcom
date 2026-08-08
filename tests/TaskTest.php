<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/TaskService.php';

function test_task_service() {
    echo "Running Task Service Test...\n";
    $db = Database::getInstance()->getConnection();

    // Create mock user
    $db->prepare("INSERT INTO users (email, password_hash, full_name) VALUES ('task_user@example.com', 'hash', 'Task User')")->execute();
    $user_id = $db->lastInsertId();

    // Create mock project
    $db->prepare("INSERT INTO projects (name, created_by) VALUES ('Test Project', ?)")->execute([$user_id]);
    $project_id = $db->lastInsertId();

    // Test Task Creation
    $task_data = [
        'project_id' => $project_id,
        'name' => 'CLI Test Task',
        'description' => 'A test description',
        'deadline' => null,
        'priority' => 'High',
        'assigned_to' => null,
        'created_by' => $user_id
    ];

    $task_id = TaskService::createTask($task_data);
    if ($task_id > 0) {
        echo "✅ Task Creation Passed\n";
    } else {
        echo "❌ Task Creation Failed\n";
    }

    // Test Fetching
    $tasks = TaskService::getUserTasks($user_id, $project_id);
    if (count($tasks) > 0 && $tasks[0]['name'] === 'CLI Test Task') {
        echo "✅ Task Retrieval Passed\n";
    } else {
        echo "❌ Task Retrieval Failed\n";
    }

    // Clean up
    $db->prepare("DELETE FROM tasks WHERE created_by = ?")->execute([$user_id]);
    $db->prepare("DELETE FROM projects WHERE created_by = ?")->execute([$user_id]);
    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
}

test_task_service();
