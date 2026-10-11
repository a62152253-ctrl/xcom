<?php
require_once __DIR__ . '/../security/Csrf.php';

// Mock session
$_SESSION = [];

echo "Testing Auth (Csrf):\n";

$token = Csrf::generateToken();
if (strlen($token) === 64) {
    echo "✔ generateToken() created token of expected length.\n";
} else {
    echo "❌ generateToken() failed.\n";
}

if (Csrf::validateToken($token)) {
    echo "✔ validateToken() returned true for correct token.\n";
} else {
    echo "❌ validateToken() failed for correct token.\n";
}

if (!Csrf::validateToken('wrong_token')) {
    echo "✔ validateToken() returned false for wrong token.\n";
} else {
    echo "❌ validateToken() failed for wrong token.\n";
}
