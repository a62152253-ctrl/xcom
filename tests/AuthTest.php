<?php
require_once __DIR__ . '/../config/database.php';

echo "Running AuthTest...\n";
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM users LIMIT 1");
if ($stmt->fetch()) {
    echo "✓ Auth dependencies load correctly.\n";
} else {
    echo "✗ Failed to query users.\n";
    exit(1);
}
