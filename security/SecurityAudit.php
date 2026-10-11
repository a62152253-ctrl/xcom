<?php
// security/SecurityAudit.php
require_once __DIR__ . '/../includes/functions.php';

class SecurityAudit {
    public static function logEvent($user_id, $event, $details) {
        log_activity($user_id, $event, $details);
    }
}
