<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../security/Permissions.php';

function run_test($name, $test_fn) {
    try {
        $test_fn();
        echo "✅ PASS: $name\n";
    } catch (\Exception $e) {
        echo "❌ FAIL: $name - " . $e->getMessage() . "\n";
    }
}

run_test("Test Permissions handles no session correctly", function() {
    // Session is not started, so it should return false
    if (\Security\Permissions::hasProjectAccess(1)) {
         throw new \Exception("Should return false when not logged in");
    }
});
