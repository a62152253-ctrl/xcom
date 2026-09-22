<?php
class SecurityAudit {
    public static function logEvent($user_id, $action, $description, $ip_address = null) {
        require_once __DIR__ . '/../config/database.php';
        try {
            $db = Database::getInstance()->getConnection();
            $ip_address = $ip_address ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $action, $description, $ip_address]);
        } catch (Exception $e) {
            error_log("SecurityAudit log failed: " . $e->getMessage());
        }
    }

    public static function checkPasswordStrength($password) {
        $errors = [];
        if (strlen($password) < 8) $errors[] = "Hasło musi mieć co najmniej 8 znaków.";
        if (!preg_match("/[A-Z]/", $password)) $errors[] = "Hasło musi zawierać co najmniej jedną wielką literę.";
        if (!preg_match("/[a-z]/", $password)) $errors[] = "Hasło musi zawierać co najmniej jedną małą literę.";
        if (!preg_match("/[0-9]/", $password)) $errors[] = "Hasło musi zawierać co najmniej jedną cyfrę.";

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
