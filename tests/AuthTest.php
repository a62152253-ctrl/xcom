<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../security/Csrf.php';

echo "Auth/Security Test: Checking CSRF Token Generation\n";
$_SESSION = [];
$token = \Security\Csrf::generateToken();
if (empty($token)) {
    echo "FAILED: CSRF token is empty\n";
    exit(1);
}
if (!\Security\Csrf::validateToken($token)) {
    echo "FAILED: CSRF token validation failed\n";
    exit(1);
}
echo "PASSED\n";
