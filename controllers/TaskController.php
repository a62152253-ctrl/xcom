<?php
require_once __DIR__ . '/../services/TaskService.php';

class TaskController {
    public static function listProjectTasks($db, $project_id) {
        return TaskService::getProjectTasks($db, $project_id);
    }

    public static function create($db, $project_id, $name, $description, $deadline, $priority, $assigned_to, $created_by) {
        return TaskService::create($db, $project_id, $name, $description, $deadline, $priority, $assigned_to, $created_by);
    }
}
