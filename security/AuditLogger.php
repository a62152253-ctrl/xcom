<?php
// security/AuditLogger.php
require_once __DIR__ . '/../config/database.php';

class AuditLogger {
    public static function log($user_id, $action, $details = null) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
            $stmt->execute([$user_id, $action, $details, $ip, $ua]);
        } catch (PDOException $e) {
            error_log("Activity log failed: " . $e->getMessage());
        }
    }
}
