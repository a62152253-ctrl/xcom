<?php
require_once __DIR__ . '/../config/database.php';

function testPasswordHashing() {
    $password = 'secretPassword123!';
    $hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 3]);

    assert(password_verify($password, $hash), 'Password should verify correctly with hash.');
    assert(!password_verify('wrongpassword', $hash), 'Incorrect password should fail verification.');

    echo "AuthTest passed.\n";
}

testPasswordHashing();
