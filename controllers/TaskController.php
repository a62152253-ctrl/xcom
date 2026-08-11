<?php

namespace Controllers;
use Services\TaskService;

require_once __DIR__ . '/../services/TaskService.php';

class TaskController {
    public static function index($db, $user_id, $filter_project, $sort_sql) {
        return TaskService::getUserTasks($db, $user_id, $filter_project, $sort_sql);
    }
}
