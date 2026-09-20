<?php
// tests/ProjectTest.php
require_once __DIR__ . '/../security/Permissions.php';

class ProjectTest {
    public function run() {
        echo "Running ProjectTest...\n";

        if (class_exists('Permissions')) {
            echo "✅ Permissions class exists.\n";
        } else {
            echo "❌ Permissions class missing.\n";
            return false;
        }

        // Test role level check
        $_SESSION['user_role'] = 'Administrator';
        if (Permissions::hasRole('Member') && Permissions::hasRole('Administrator') && !Permissions::hasRole('Owner')) {
            echo "✅ Permissions role check works.\n";
        } else {
            echo "❌ Permissions role check failed.\n";
            return false;
        }

        echo "✅ ProjectTest passed.\n";
        return true;
    }
}

$test = new ProjectTest();
$test->run();
