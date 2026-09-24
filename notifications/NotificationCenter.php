<?php
// notifications/NotificationCenter.php
require_once __DIR__ . '/NotificationService.php';

class NotificationCenter {
    public static function notifyNewTask($user_id, $task_name, $task_id) {
        return NotificationService::create(
            $user_id,
            'new_task',
            'Nowe zadanie',
            "Przydzielono Ci nowe zadanie: {$task_name}",
            $task_id
        );
    }

    public static function notifyDeadline($user_id, $task_name, $task_id) {
        return NotificationService::create(
            $user_id,
            'deadline',
            'Zbliżający się termin',
            "Zadanie {$task_name} wkrótce się kończy.",
            $task_id
        );
    }

    public static function notifyProjectCompleted($user_id, $project_name, $project_id) {
         return NotificationService::create(
            $user_id,
            'project_completed',
            'Projekt ukończony',
            "Projekt {$project_name} został oznaczony jako ukończony.",
            $project_id
        );
    }

    public static function notifySystemInfo($user_id, $message) {
         return NotificationService::create(
            $user_id,
            'system_info',
            'Informacja systemowa',
            $message
        );
    }
}
