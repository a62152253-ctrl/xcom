<?php
require_once __DIR__ . '/../config/database.php';

class AuthTest {
    public static function run() {
        echo "Running AuthTest...\n";

        $db = Database::getInstance()->getConnection();

        // Setup test user
        $email = 'authtest_' . time() . '@example.com';
        $password = 'password123';
        $hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);

        $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, role, status) VALUES (?, ?, ?, 'Member', 'Active')");
        $stmt->execute([$email, $hash, 'Test User']);
        $user_id = $db->lastInsertId();

        // Test login success
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            echo "✅ Login success test passed\n";
        } else {
            echo "❌ Login success test failed\n";
        }

        // Test login failure
        if (!$user || !password_verify('wrongpassword', $user['password_hash'])) {
            echo "✅ Login failure test passed\n";
        } else {
            echo "❌ Login failure test failed\n";
        }

        // Cleanup
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
    }
}

AuthTest::run();
