<?php
// controllers/TaskController.php
require_once __DIR__ . '/../services/TaskService.php';

class TaskController {
    public static function indexAction($user_id, $filter_project) {
        return TaskService::getUserTasks($user_id, $filter_project);
    }
}
