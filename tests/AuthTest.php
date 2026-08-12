<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

echo "Running AuthTest...\n";
$db = Database::getInstance()->getConnection();

// Create test user
$email = "testauth" . time() . "@example.com";
$password = "SecretPassword123!";
$hash = password_hash($password, PASSWORD_ARGON2ID);
$stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, role, status) VALUES (?, ?, 'Test User', 'Member', 'Active')");
$stmt->execute([$email, $hash]);
$userId = $db->lastInsertId();

echo "User created. Testing login...\n";

// Test successful login
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    echo "✅ Success: Correct password verified.\n";
} else {
    echo "❌ Error: Correct password verification failed.\n";
}

// Test failed login
if ($user && password_verify("WrongPassword123!", $user['password_hash'])) {
    echo "❌ Error: Incorrect password verified as correct.\n";
} else {
    echo "✅ Success: Incorrect password correctly rejected.\n";
}

// Cleanup
$db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
echo "AuthTest complete.\n";
