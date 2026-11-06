<?php
// services/TaskService.php
require_once __DIR__ . '/../models/Task.php';

class TaskService {
    public static function getUserTasks($db, $user_id, $filter_project = 0) {
        return Task::getUserTasks($db, $user_id, $filter_project);
    }

    public static function getTaskById($db, $task_id) {
        return Task::getTaskById($db, $task_id);
    }
}
