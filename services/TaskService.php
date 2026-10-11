<?php
// services/TaskService.php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../config/database.php';

class TaskService {
    public static function getUserTasks($user_id, $filter_project) {
        $db = Database::getInstance()->getConnection();
        return Task::getUserTasks($db, $user_id, $filter_project);
    }
}
