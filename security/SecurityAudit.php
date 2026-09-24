<?php
// security/SecurityAudit.php
require_once __DIR__ . '/../config/database.php';

class SecurityAudit {
    public static function log($user_id, $action, $details = null) {
        try {
            $db = Database::getInstance()->getConnection();
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmt = $db->prepare("
                INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $details_json = $details ? json_encode($details) : null;

            $stmt->execute([
                $user_id,
                $action,
                $details_json,
                $ip_address,
                $user_agent
            ]);
            return true;
        } catch (Exception $e) {
             error_log("SecurityAudit Error: " . $e->getMessage());
             return false;
        }
    }
}
