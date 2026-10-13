<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/TaskController.php';

echo "TaskTest: Checking TaskController class\n";
if (!class_exists('TaskController')) {
    echo "FAILED: TaskController class not found\n";
    exit(1);
}
echo "PASSED\n";
