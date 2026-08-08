<?php
namespace Notifications;

use Database;
use PDOException;

class NotificationCenter {
    public static function getUnreadCount(int $user_id): int {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user_id]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public static function getLatest(int $user_id, int $limit = 10): array {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public static function markAllAsRead(int $user_id): void {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Failed to mark notifications as read: " . $e->getMessage());
        }
    }
}
