<?php
require_once __DIR__ . '/../config/database.php';

echo "Running ProjectTest...\n";
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM projects LIMIT 1");
if ($stmt->fetch() !== false) { // can be empty, but query should not fail
    echo "✓ Projects query works.\n";
} else {
    echo "✗ Failed to query projects.\n";
    exit(1);
}
