<?php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../services/TaskService.php';

function testTaskServiceExists() {
    assert(class_exists('TaskService'), 'TaskService class should exist.');
    assert(class_exists('Task'), 'Task model should exist.');
    echo "TaskTest passed.\n";
}

testTaskServiceExists();
