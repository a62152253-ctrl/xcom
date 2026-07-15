<?php
// includes/ratelimit.php - Simple rate limiting
class RateLimit {
    private $db;
    private $limits = ['login' => 5, 'login_window' => 300];
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function check($identifier, $action = 'login', $limit = 5, $window = 300) {
        $key = hash_hmac('sha256', $action . ':' . $identifier, 'ratelimit_secret_key');
        $now = time();
        
        try {
            // Clean old attempts
            $this->db->prepare("DELETE FROM rate_limit WHERE ip_hash = ? AND action = ? AND attempt_time < ?")
                ->execute([$key, $action, $now - $window]);
            
            // Count attempts in window
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM rate_limit WHERE ip_hash = ? AND action = ? AND attempt_time > ?");
            $stmt->execute([$key, $action, $now - $window]);
            $attempts = (int)$stmt->fetchColumn();
            
            if ($attempts >= $limit) {
                return false; // Rate limited
            }
            
            // Log attempt
            $this->db->prepare("INSERT INTO rate_limit (ip_hash, action, attempt_time) VALUES (?, ?, ?)")
                ->execute([$key, $action, $now]);
            
            return true;
        } catch (Exception $e) {
            error_log("Rate limit check failed: " . $e->getMessage());
            return true; // Fail open
        }
    }
}
