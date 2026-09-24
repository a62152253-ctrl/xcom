<?php
// controllers/TaskController.php
require_once __DIR__ . '/../services/TaskService.php';

class TaskController {
    public static function getUserTasks($user_id, $project_id = 0) {
        return TaskService::getUserTasks($user_id, $project_id);
    }

    public static function getTaskById($task_id) {
        return TaskService::getTaskById($task_id);
    }
}
