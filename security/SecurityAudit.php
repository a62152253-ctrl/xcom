<?php
// security/SecurityAudit.php

class SecurityAudit {
    public static function log($user_id, $action, $details) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $stmt->execute([$user_id, $action, $details, $ip, $ua]);
        } catch (Exception $e) {
            // Silently fail if audit log insertion fails
            error_log("Failed to log security audit: " . $e->getMessage());
        }
    }
}
