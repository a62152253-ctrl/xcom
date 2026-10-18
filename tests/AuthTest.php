<?php
// tests/AuthTest.php
require_once __DIR__ . '/../includes/session.php';

echo "Running AuthTest...\n";

// Test 1: is_logged_in() function
$_SESSION['user_id'] = 1;
$_SESSION['user_status'] = 'Active';

if (is_logged_in()) {
    echo "[PASS] is_logged_in() correctly identifies a valid active session.\n";
} else {
    echo "[FAIL] is_logged_in() failed to identify a valid session.\n";
}

$_SESSION['user_status'] = 'Banned';
if (!is_logged_in()) {
    echo "[PASS] is_logged_in() correctly rejects a banned user session.\n";
} else {
    echo "[FAIL] is_logged_in() failed to reject a banned user session.\n";
}

// Test 2: Password hashing parameters
$password = "testpassword123";
$hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);
$info = password_get_info($hash);

if ($info['algoName'] === 'argon2id') {
    echo "[PASS] Password hashed using argon2id.\n";
} else {
    echo "[FAIL] Password hashed using incorrect algorithm: " . $info['algoName'] . "\n";
}

if (password_verify($password, $hash)) {
    echo "[PASS] Password successfully verified against Argon2id hash.\n";
} else {
    echo "[FAIL] Password verification failed.\n";
}

echo "AuthTest Complete.\n";
