<?php
namespace Notifications;

require_once __DIR__ . '/../config/database.php';

class NotificationService {
    public static function send($user_id, $title, $message, $type = 'info') {
        try {
            $db = \Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $message, $type]);
        } catch (\PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
        }
    }

    public static function sendTaskAssign($user_id, $task_name) {
        self::send($user_id, 'Przypisano nowe zadanie', "Zostałeś przypisany do zadania: $task_name", 'task_assign');
    }

    public static function sendTaskStatusChange($user_id, $task_name, $status) {
        self::send($user_id, 'Status zmieniony', "Zadanie '$task_name' zmieniono na: $status", 'status_change');
    }

    public static function sendNewComment($user_id, $task_name) {
        self::send($user_id, 'Nowy komentarz', "Dodano komentarz do: $task_name", 'comment');
    }

    public static function sendProjectInvite($user_id, $project_name, $role) {
        self::send($user_id, 'Dodano do projektu', "Zostałeś dodany do projektu '$project_name' jako $role.", 'system');
    }
}
