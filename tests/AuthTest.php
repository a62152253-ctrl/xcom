<?php
// tests/AuthTest.php
require_once __DIR__ . '/../security/Permissions.php';

class AuthTest {
    public function run() {
        echo "Running AuthTest...\n";

        // Mock session
        $_SESSION['user_role'] = 'Administrator';
        $_SESSION['user_id'] = 1;
        $_SESSION['user_status'] = 'Active'; // needed for is_logged_in()

        $this->testHasRole();
        $this->testHasProjectAccessAdminBypass();

        echo "AuthTest passed.\n";
    }

    private function testHasRole() {
        if (!Permissions::hasRole('Administrator')) {
            throw new Exception("testHasRole failed for direct match");
        }
        if (!Permissions::hasRole(['Member', 'Administrator'])) {
            throw new Exception("testHasRole failed for array match");
        }
        if (Permissions::hasRole('Owner')) {
            throw new Exception("testHasRole failed: matched invalid role");
        }
    }

    private function testHasProjectAccessAdminBypass() {
        // Administrator should bypass project access checks globally
        // (This tests the first short-circuit in Permissions::hasProjectAccess)
        // Note: we can't test db paths without a real connection
        if (!Permissions::hasProjectAccess(999, 'Owner')) {
            throw new Exception("Admin bypass failed in hasProjectAccess");
        }
    }
}

session_start();
$test = new AuthTest();
$test->run();
