<?php
// services/TaskService.php

require_once __DIR__ . '/../models/Task.php';

class TaskService {
    public static function getUserTasks($user_id, $project_id = 0) {
        return Task::getTasksByUser($user_id, $project_id);
    }

    public static function getTaskDetails($task_id) {
        return Task::getTaskById($task_id);
    }
}
