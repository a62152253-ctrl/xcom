<?php
// notifications/NotificationService.php

require_once __DIR__ . '/../config/database.php';

class NotificationService {

    /**
     * Send a notification to a user.
     * @param int $user_id
     * @param string $title
     * @param string $message
     * @param string $type enum('nowe zadanie', 'deadline', 'ukończony projekt', 'systemowe info')
     */
    public static function send($user_id, $title, $message, $type = 'systemowe info') {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $message, $type]);
            return true;
        } catch (PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
            return false;
        }
    }

    public static function getUnread($user_id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    public static function markAsRead($notification_id, $user_id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notification_id, $user_id]);
    }
}
