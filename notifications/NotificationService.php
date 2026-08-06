<?php
namespace Notifications;

class NotificationService {
    public static function send($db, $user_id, $title, $message, $type = 'info') {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        return $stmt->execute([$user_id, $title, $message, $type]);
    }
}
