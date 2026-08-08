<?php
namespace Security;

use Database;
use PDOException;

class SecurityAudit {
    public static function logActivity(?int $user_id, string $action, ?string $details = null): void {
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

    public static function checkFailedLogins(string $ip, int $minutes = 5, int $max_attempts = 5): bool {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT COUNT(*)
                FROM activity_logs
                WHERE action = 'failed_login'
                  AND ip_address = ?
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $stmt->execute([$ip, $minutes]);
            $attempts = (int)$stmt->fetchColumn();
            return $attempts < $max_attempts;
        } catch (PDOException $e) {
            error_log("Failed logins check error: " . $e->getMessage());
            return true; // Fail open to not block valid users on DB error, though risky.
        }
    }
}
