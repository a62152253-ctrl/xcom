<?php
// notifications/NotificationService.php
require_once __DIR__ . '/../config/database.php';

class NotificationService {
    public static function create($user_id, $title, $message, $type = 'info') {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $message, $type]);
        } catch (PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
        }
    }
}
