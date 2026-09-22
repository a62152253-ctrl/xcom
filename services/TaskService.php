<?php
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../config/database.php';

class TaskService {
    public static function getUserTasks($user_id, $project_id = null) {
        $db = Database::getInstance()->getConnection();
        return Task::getAllByUser($db, $user_id, $project_id);
    }

    public static function getTask($task_id) {
        $db = Database::getInstance()->getConnection();
        return Task::getById($db, $task_id);
    }

    public static function createTask($data) {
        $db = Database::getInstance()->getConnection();
        $task_id = Task::create($db, $data);

        require_once __DIR__ . '/../notifications/NotificationService.php';
        if (!empty($data['assigned_to'])) {
            NotificationService::notifyNewTask($data['assigned_to'], $data['title'], $task_id);
        }

        return $task_id;
    }

    public static function changeStatus($task_id, $status) {
        $db = Database::getInstance()->getConnection();
        return Task::updateStatus($db, $task_id, $status);
    }

    public static function deleteTask($task_id) {
        $db = Database::getInstance()->getConnection();
        return Task::delete($db, $task_id);
    }
}
