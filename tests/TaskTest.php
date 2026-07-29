<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/TaskService.php';

function run_test($name, $test_fn) {
    try {
        $test_fn();
        echo "✅ PASS: $name\n";
    } catch (\Exception $e) {
        echo "❌ FAIL: $name - " . $e->getMessage() . "\n";
    }
}

run_test("Test TaskService instantiates Tasks", function() {
    // Note: Depends on real DB, we are just verifying the logic doesn't crash
    // and returns an array.
    $tasks = \Services\TaskService::getUserTasks(1);
    if (!is_array($tasks)) {
        throw new \Exception("Expected array");
    }
});
