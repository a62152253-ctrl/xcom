<?php
// security/SecurityAudit.php
require_once __DIR__ . '/../config/database.php';

class SecurityAudit {
    public static function log($user_id, $action, $details = '') {
        try {
            $db = Database::getInstance()->getConnection();
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $action, $details, $ip_address, $user_agent]);
        } catch (Exception $e) {
            error_log("SecurityAudit log failed: " . $e->getMessage());
        }
    }
}
