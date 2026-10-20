<?php
namespace Security;

class SecurityAudit {
    public static function log($action, $user_id = null, $details = '') {
        $db = \Database::getInstance()->getConnection();
        $user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $action, $details, $ip_address, $user_agent]);
    }
}
