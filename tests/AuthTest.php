<?php
require_once __DIR__ . '/MockSchema.php';

echo "Running AuthTest...\n";
$db = new PDO('sqlite::memory:');
MockSchema::create($db);

$db->exec("INSERT INTO users (email, password_hash, status) VALUES ('test@example.com', '" . password_hash('password', PASSWORD_ARGON2ID) . "', 'Active')");

$stmt = $db->prepare("SELECT * FROM users WHERE email = 'test@example.com'");
$stmt->execute();
$user = $stmt->fetch();

if ($user && password_verify('password', $user['password_hash'])) {
    echo "AuthTest Passed: Login OK\n";
} else {
    echo "AuthTest Failed: Login Error\n";
    exit(1);
}
