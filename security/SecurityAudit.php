<?php
// security/SecurityAudit.php
class SecurityAudit {
    public static function log($db, $user_id, $action, $details = '') {
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $action, $details]);
    }
}
