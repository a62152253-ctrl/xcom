<?php
// notifications/NotificationCenter.php

require_once __DIR__ . '/NotificationService.php';

class NotificationCenter {

    public static function notifyNewTask($user_id, $task_name, $project_name) {
        $title = "Nowe Zadanie";
        $message = "Zostałeś przypisany do zadania '$task_name' w projekcie '$project_name'.";
        return NotificationService::send($user_id, $title, $message, 'nowe zadanie');
    }

    public static function notifyDeadline($user_id, $task_name, $deadline) {
        $title = "Zbliżający się deadline";
        $message = "Zadanie '$task_name' musi zostać ukończone do $deadline.";
        return NotificationService::send($user_id, $title, $message, 'deadline');
    }

    public static function notifyProjectCompleted($user_id, $project_name) {
        $title = "Ukończony projekt";
        $message = "Projekt '$project_name' został pomyślnie ukończony.";
        return NotificationService::send($user_id, $title, $message, 'ukończony projekt');
    }

    public static function notifySystemInfo($user_id, $info) {
        $title = "Informacja systemowa";
        return NotificationService::send($user_id, $title, $info, 'systemowe info');
    }
}
