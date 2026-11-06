<?php
// controllers/TaskController.php
require_once __DIR__ . '/../services/TaskService.php';

class TaskController {
    public static function listTasks($db, $user_id, $filter_project = 0) {
        return TaskService::getUserTasks($db, $user_id, $filter_project);
    }

    public static function getTask($db, $task_id) {
        return TaskService::getTaskById($db, $task_id);
    }
}
