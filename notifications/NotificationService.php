<?php
// notifications/NotificationService.php
require_once __DIR__ . '/../config/database.php';

class NotificationService {
    public static function create($user_id, $title, $message, $type = 'info') {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $message, $type]);
            return $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
            return false;
        }
    }

    public static function getUnreadCount($user_id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    }

    public static function markAllAsRead($user_id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return true;
    }
}
