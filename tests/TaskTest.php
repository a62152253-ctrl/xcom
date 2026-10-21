<?php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/MockSchema.php';

echo "Running TaskTest...\n";
$db = new PDO('sqlite::memory:');
MockSchema::create($db);

$db->exec("INSERT INTO projects (id, name, created_by, is_archived) VALUES (1, 'Test Proj', 1, 0)");
$db->exec("INSERT INTO tasks (id, project_id, name, priority, deadline) VALUES (1, 1, 'Test Task', 'High', '2025-01-01')");

$tasks = Task::getUserTasks($db, 1);
if (count($tasks) === 1 && $tasks[0]['name'] === 'Test Task') {
    echo "TaskTest Passed: CRUD Task OK\n";
} else {
    echo "TaskTest Failed: Task CRUD Error\n";
    exit(1);
}
