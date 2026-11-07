<?php
// notifications/NotificationCenter.php
require_once __DIR__ . '/NotificationService.php';

class NotificationCenter {
    public static function taskAssigned($user_id, $task_name) {
        return NotificationService::createNotification($user_id, 'Nowe zadanie', "Zostałeś przypisany do zadania: $task_name", 'task_assign');
    }

    public static function taskStatusChanged($user_id, $task_name, $new_status) {
        return NotificationService::createNotification($user_id, 'Status zmieniony', "Zadanie '$task_name' ma nowy status: $new_status", 'status_change');
    }

    public static function taskDeadlineApproaching($user_id, $task_name) {
        return NotificationService::createNotification($user_id, 'Zbliża się termin', "Zadanie '$task_name' ma dzisiaj termin", 'deadline');
    }

    public static function projectCompleted($user_id, $project_name) {
        return NotificationService::createNotification($user_id, 'Projekt ukończony', "Gratulacje, projekt '$project_name' został ukończony!", 'project_complete');
    }

    public static function newComment($user_id, $task_name) {
        return NotificationService::createNotification($user_id, 'Nowy komentarz', "Dodano komentarz do zadania: $task_name", 'comment');
    }
}
