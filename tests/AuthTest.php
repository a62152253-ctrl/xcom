<?php
// Simple auth logic assertion
$hash = password_hash('test', PASSWORD_ARGON2ID);
assert(password_verify('test', $hash));
echo "Auth Test Passed\n";
