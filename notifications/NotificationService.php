<?php
// notifications/NotificationService.php
require_once __DIR__ . '/../config/database.php';

class NotificationService {
    public static function send($user_id, $title, $message, $type = 'info') {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $message, $type]);
        } catch (PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
        }
    }

    public static function sendTaskNotification($user_id, $task_name) {
        self::send($user_id, 'Nowe zadanie', "Dodano zadanie: {$task_name}", 'info');
    }

    public static function sendDeadlineNotification($user_id, $task_name, $deadline) {
        self::send($user_id, 'Zbliżający się termin', "Zadanie {$task_name} upływa {$deadline}", 'warning');
    }

    public static function sendProjectFinished($user_id, $project_name) {
        self::send($user_id, 'Ukończony projekt', "Projekt {$project_name} został zakończony", 'success');
    }

    public static function sendSystemInfo($user_id, $message) {
        self::send($user_id, 'Systemowe info', $message, 'info');
    }
}
