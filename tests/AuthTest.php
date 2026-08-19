<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Very basic test for password verification
$password = 'secret123';
$hash = password_hash($password, PASSWORD_ARGON2ID);

echo "Running AuthTest...\n";
if (password_verify($password, $hash)) {
    echo "✓ password_verify works correctly.\n";
} else {
    echo "✗ password_verify failed.\n";
    exit(1);
}
echo "AuthTest passed!\n";
