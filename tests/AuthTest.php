<?php
class AuthTest {
    public static function run() {
        echo "Running AuthTest...\n";
        $success = true;

        $password = 'Secret123!';
        $hash = password_hash($password, PASSWORD_ARGON2ID);

        if (!password_verify($password, $hash)) {
            echo "❌ AuthTest: Password verification failed\n";
            $success = false;
        } else {
            echo "✅ AuthTest: Password hashing and verification passed\n";
        }

        return $success;
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    AuthTest::run();
}
