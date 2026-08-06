<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/TaskService.php';

echo "Running TaskTest...\n";
$db = Database::getInstance()->getConnection();
$tasks = \Services\TaskService::getUserTasks($db, 1);
if (is_array($tasks)) {
    echo "✓ TaskService loads and returns tasks successfully.\n";
} else {
    echo "✗ Failed to fetch tasks.\n";
    exit(1);
}
