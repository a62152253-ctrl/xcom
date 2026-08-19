<?php
require_once __DIR__ . '/../config/database.php';

class NotificationService {
    public static function notify($user_id, $title, $message, $type = 'info') {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $message, $type]);
        } catch (PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
        }
    }

    public static function notifyNewTask($user_id, $task_name) {
        self::notify($user_id, 'Przypisano nowe zadanie', "Zostałeś przypisany do zadania: $task_name", 'task_assign');
    }

    public static function notifyStatusChange($user_id, $task_name, $status) {
        self::notify($user_id, 'Status zmieniony', "Zadanie '$task_name' zmieniono na: $status", 'status_change');
    }

    public static function notifyNewComment($user_id, $task_name) {
        self::notify($user_id, 'Nowy komentarz', "Dodano komentarz do: $task_name", 'comment');
    }
}
