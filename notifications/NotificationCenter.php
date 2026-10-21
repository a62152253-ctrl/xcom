<?php
// notifications/NotificationCenter.php
require_once __DIR__ . '/../config/database.php';

class NotificationCenter {
    public static function getUnread($user_id, $limit = 8) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
            // Workaround for PDO binding INT limit
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed fetching notifications: " . $e->getMessage());
            return [];
        }
    }

    public static function markAllRead($user_id) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$user_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Failed marking notifications read: " . $e->getMessage());
            return false;
        }
    }
}
