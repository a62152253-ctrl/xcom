<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/TaskService.php';

echo "TaskTest Running...\n";

$db = Database::getInstance()->getConnection();
// Ensure the TaskService can be called
$tasks = TaskService::getUserTasks($db, 1);
if (is_array($tasks)) {
    echo "TaskTest Passed: Tasks retrieved successfully.\n";
} else {
    echo "TaskTest Failed: Task retrieval returned non-array.\n";
    exit(1);
}
