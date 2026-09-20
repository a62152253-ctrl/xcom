<?php
// security/SecurityAudit.php

require_once __DIR__ . '/../config/database.php';

class SecurityAudit {
    public static function logEvent($user_id, $event_type, $details) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
            $stmt->execute([$user_id, $event_type, is_string($details) ? $details : json_encode($details), $ip, $ua]);
        } catch (PDOException $e) {
            error_log("SecurityAudit logEvent failed: " . $e->getMessage());
        }
    }

    public static function checkSqlInjection($input) {
        $pattern = '/(union|select|insert|update|delete|drop|alter|--|;)/i';
        return preg_match($pattern, $input);
    }

    public static function verifyPasswordStrength($password) {
        return strlen($password) >= 8;
    }
}
