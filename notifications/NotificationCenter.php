<?php

namespace Notifications;
use Database;
use \PDOException;

class NotificationCenter {
    public static function getUnread($db, $user_id) {
        try {
            $stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC');
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching notifications: " . $e->getMessage());
            return [];
        }
    }
}
