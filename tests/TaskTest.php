<?php
// tests/TaskTest.php
require_once __DIR__ . '/../models/Task.php';

echo "Running TaskTest...\n";

// Setup memory database for mocking
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Mock Schema
$db->exec("
    CREATE TABLE tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER,
        name TEXT,
        description TEXT,
        deadline DATE,
        priority TEXT,
        status TEXT,
        assigned_to INTEGER,
        created_by INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");

// Test Task Creation
$task_id = Task::create($db, 1, 'Test Task', 'This is a test description', '2024-12-31', 'High', 2, 1);
if ($task_id > 0) {
    echo "[PASS] Task created successfully, ID: $task_id\n";
} else {
    echo "[FAIL] Task creation failed.\n";
}

// Test Status Update
Task::updateStatus($db, $task_id, 'In Progress');
$stmt = $db->prepare("SELECT status FROM tasks WHERE id = ?");
$stmt->execute([$task_id]);
$status = $stmt->fetchColumn();

if ($status === 'In Progress') {
    echo "[PASS] Task status updated correctly.\n";
} else {
    echo "[FAIL] Task status update failed. Current: $status\n";
}

// Test Task Deletion (partial due to missing relations, but testing core function logic execution)
try {
    $db->exec("CREATE TABLE task_comments (task_id INTEGER);");
    $db->exec("CREATE TABLE subtasks (task_id INTEGER);");
    $db->exec("CREATE TABLE task_files (task_id INTEGER);");
    Task::delete($db, $task_id);

    $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        echo "[PASS] Task deleted successfully.\n";
    } else {
        echo "[FAIL] Task deletion failed.\n";
    }
} catch (Exception $e) {
    echo "[FAIL] Task deletion raised an error: " . $e->getMessage() . "\n";
}

echo "TaskTest Complete.\n";
