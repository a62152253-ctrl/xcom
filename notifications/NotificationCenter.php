<?php
// notifications/NotificationCenter.php
require_once __DIR__ . '/NotificationService.php';

class NotificationCenter {
    public static function notifyNewTask($user_id, $task_name) {
        NotificationService::create($user_id, 'Przypisano nowe zadanie', "Zostałeś przypisany do zadania: $task_name", 'task_assign');
    }

    public static function notifyTaskStatusChange($user_id, $task_name, $new_status) {
        NotificationService::create($user_id, 'Status zmieniony', "Zadanie '$task_name' zmieniono na: $new_status", 'status_change');
    }

    public static function notifyProjectInvite($user_id, $project_name, $role) {
        NotificationService::create($user_id, 'Dodano do projektu', "Zostałeś dodany do projektu '$project_name' jako $role.", 'system');
    }
}
