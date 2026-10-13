<?php
require_once __DIR__ . '/../models/Task.php';

class TaskService {
    public static function getProjectTasks($db, $project_id) {
        return Task::getProjectTasks($db, $project_id);
    }

    public static function create($db, $project_id, $name, $description, $deadline, $priority, $assigned_to, $created_by) {
        return Task::create($db, $project_id, $name, $description, $deadline, $priority, $assigned_to, $created_by);
    }
}
