<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

function test_auth_login() {
    echo "Running Auth Login Test...\n";
    $db = Database::getInstance()->getConnection();

    // Create a mock user
    $email = 'test_auth@example.com';
    $password = 'Password123!';
    $hash = password_hash($password, PASSWORD_ARGON2ID);

    $db->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);
    $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, status) VALUES (?, ?, 'Test User', 'Active')");
    $stmt->execute([$email, $hash]);

    // Test verification
    $stmt_fetch = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt_fetch->execute([$email]);
    $user = $stmt_fetch->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        echo "✅ Auth Login Validation Passed\n";
    } else {
        echo "❌ Auth Login Validation Failed\n";
    }

    $db->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);
}

test_auth_login();
