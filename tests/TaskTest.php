<?php
require_once __DIR__ . '/../services/TaskService.php';
assert(method_exists(\Services\TaskService::class, 'getUserTasks'));
echo "Task Test Passed\n";
