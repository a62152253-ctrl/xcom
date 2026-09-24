<?php
// notifications/NotificationService.php
require_once __DIR__ . '/../config/database.php';

class NotificationService {
    public static function create($user_id, $type, $title, $message, $related_id = null) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_id, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            $stmt->execute([$user_id, $type, $title, $message, $related_id]);
            return true;
        } catch (Exception $e) {
            error_log("NotificationService Error: " . $e->getMessage());
            return false;
        }
    }
}
