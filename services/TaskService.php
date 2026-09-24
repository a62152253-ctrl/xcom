<?php
// services/TaskService.php
require_once __DIR__ . '/../models/Task.php';

class TaskService {
    public static function getUserTasks($user_id, $project_id = 0) {
        return Task::getUserTasks($user_id, $project_id);
    }

    public static function getTaskById($task_id) {
        return Task::getTaskById($task_id);
    }
}
