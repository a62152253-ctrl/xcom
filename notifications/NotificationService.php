<?php

namespace Notifications;
use Database;
use \PDOException;

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
}
