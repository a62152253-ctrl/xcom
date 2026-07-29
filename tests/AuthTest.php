<?php


function run_test($name, $test_fn) {
    try {
        $test_fn();
        echo "✅ PASS: $name\n";
    } catch (\Exception $e) {
        echo "❌ FAIL: $name - " . $e->getMessage() . "\n";
    }
}

// Ensure the db is up and running.


run_test("Test password_verify works", function() {
    $password = "Secret123!";
    $hash = password_hash($password, PASSWORD_ARGON2ID);
    if (!password_verify($password, $hash)) {
        throw new \Exception("password_verify failed");
    }
    if (password_verify("wrong", $hash)) {
        throw new \Exception("password_verify succeeded with wrong password");
    }
});
