<?php
class SecurityAudit {
    public static function log($user_id, $action, $details = '') {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt->execute([$user_id, $action, $details, $ip]);
    }
}
