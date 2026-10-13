<?php
// models/User.php
require_once __DIR__ . '/../config/database.php';

class User {
    public static function getAllActiveUsers($db) {
        $stmt = $db->query("SELECT id, full_name, email FROM users WHERE status='Active' ORDER BY full_name ASC");
        return $stmt->fetchAll();
    }

    public static function findByEmail($db, $email) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
}
