<?php
// api/test_login.php - Quick login for testing
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';

start_secure_session();

$db = Database::getInstance()->getConnection();

// Get or create test user
$stmt = $db->prepare("SELECT * FROM users WHERE email = 'test@example.com' LIMIT 1");
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    // Create test user with password 'password'
    $hash = password_hash('password', PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);
    $db->prepare("INSERT INTO users (email, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, ?)")
        ->execute(['test@example.com', $hash, 'Test User', 'Owner', 'Active']);
    
    $user_id = $db->lastInsertId();
    
    // Create settings
    $db->prepare("INSERT INTO settings (user_id, theme, language) VALUES (?, ?, ?)")
        ->execute([$user_id, 'dark', 'pl']);
    
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

// Log in the test user
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_status'] = $user['status'];

echo json_encode(['success' => true, 'message' => 'Zalogowany jako ' . $user['full_name']]);
exit;
