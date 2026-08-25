<?php
// notifications/NotificationCenter.php

require_once __DIR__ . '/NotificationService.php';

class NotificationCenter {
    public static function notifyNewTask($user_id, $task_name) {
        return NotificationService::create($user_id, 'Nowe zadanie', "Dodano nowe zadanie: {$task_name}", 'new_task');
    }

    public static function notifyDeadline($user_id, $task_name, $deadline) {
        return NotificationService::create($user_id, 'Zbliżający się termin', "Zadanie {$task_name} ma termin do {$deadline}", 'deadline');
    }

    public static function notifyProjectCompleted($user_id, $project_name) {
        return NotificationService::create($user_id, 'Projekt ukończony', "Projekt {$project_name} został oznaczony jako ukończony", 'completed_project');
    }

    public static function notifySystemInfo($user_id, $message) {
        return NotificationService::create($user_id, 'Informacja systemowa', $message, 'system_info');
    }
}
