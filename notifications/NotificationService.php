<?php
namespace Notifications;

use Database;
use PDOException;

class NotificationService {
    public static function notify(int $user_id, string $title, string $message, string $type = 'info'): void {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $title, $message, $type]);
        } catch (PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
        }
    }
}
