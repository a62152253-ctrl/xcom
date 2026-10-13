<?php
namespace Notifications;

class NotificationService {
    public static function create($db, $user_id, $title, $message, $type = 'info') {
        try {
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$user_id, $title, $message, $type]);
        } catch (\PDOException $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }

    public static function getUnread($db, $user_id) {
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public static function markAsRead($db, $notification_id, $user_id) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notification_id, $user_id]);
    }
}
