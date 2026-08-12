<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../services/TaskService.php';

echo "Running TaskTest...\n";
$db = Database::getInstance()->getConnection();
$taskService = new \Services\TaskService($db);

// Create test user & project
$email = "testtask" . time() . "@example.com";
$db->prepare("INSERT INTO users (email, password_hash, full_name, role, status) VALUES (?, 'hash', 'Test User', 'Member', 'Active')")->execute([$email]);
$userId = $db->lastInsertId();

$db->prepare("INSERT INTO projects (name, created_by, color) VALUES ('Test Project', ?, '#000000')")->execute([$userId]);
$projectId = $db->lastInsertId();

// Create Task
$db->prepare("INSERT INTO tasks (project_id, name, description, status, priority, assigned_to) VALUES (?, 'Test Task', 'Desc', 'To Do', 'Medium', ?)")->execute([$projectId, $userId]);
$taskId = $db->lastInsertId();
echo "✅ Success: Task Created (ID: $taskId).\n";

// Read Task
$task = $taskService->getTaskById($taskId);
if ($task && $task['name'] === 'Test Task') {
    echo "✅ Success: Task Read correctly.\n";
} else {
    echo "❌ Error: Task Read failed.\n";
}

// Update Task
$db->prepare("UPDATE tasks SET status = 'Done' WHERE id = ?")->execute([$taskId]);
$task = $taskService->getTaskById($taskId);
if ($task && $task['status'] === 'Done') {
    echo "✅ Success: Task Updated correctly.\n";
} else {
    echo "❌ Error: Task Update failed.\n";
}

// Delete Task
$db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$taskId]);
$task = $taskService->getTaskById($taskId);
if (!$task) {
    echo "✅ Success: Task Deleted correctly.\n";
} else {
    echo "❌ Error: Task Delete failed.\n";
}

// Cleanup
$db->prepare("DELETE FROM projects WHERE id = ?")->execute([$projectId]);
$db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
echo "TaskTest complete.\n";
