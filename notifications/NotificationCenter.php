<?php
// notifications/NotificationCenter.php
require_once __DIR__ . '/../config/database.php';

class NotificationCenter {
    public static function getUnread($user_id) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Failed to get unread notifications: " . $e->getMessage());
            return [];
        }
    }

    public static function markAllAsRead($user_id) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user_id]);
        } catch (Exception $e) {
            error_log("Failed to mark notifications as read: " . $e->getMessage());
        }
    }
}
