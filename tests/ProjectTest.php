<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/TaskService.php';

echo "ProjectTest Running...\n";

$db = Database::getInstance()->getConnection();
$projects = TaskService::getProjects($db, 1);
if (is_array($projects)) {
    echo "ProjectTest Passed: Projects retrieved successfully.\n";
} else {
    echo "ProjectTest Failed: Project retrieval returned non-array.\n";
    exit(1);
}
