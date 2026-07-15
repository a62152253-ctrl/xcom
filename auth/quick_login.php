<?php
// auth/quick_login.php - Quick test login
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

start_secure_session();

$db = Database::getInstance()->getConnection();

// Create or get test user
$stmt = $db->prepare("SELECT * FROM users WHERE email = 'test@example.com' LIMIT 1");
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    $hash = password_hash('password', PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);
    $db->prepare("INSERT INTO users (email, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, ?)")
        ->execute(['test@example.com', $hash, 'Test User', 'Owner', 'Active']);
    
    $user_id = $db->lastInsertId();
    $db->prepare("INSERT INTO settings (user_id, theme, language) VALUES (?, ?, ?)")
        ->execute([$user_id, 'dark', 'pl']);
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

// Log in
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_status'] = $user['status'];

header("Location: /pages/dashboard.php");
exit;
