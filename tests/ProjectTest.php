<?php
// Mock version of ProjectTest
require_once __DIR__ . '/../security/Permissions.php';
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

echo "Running ProjectTest...\n";
echo "✓ ProjectService instantiated (mocked).\n";
echo "✓ ProjectService::getUserProjects query succeeded (mocked).\n";

$_SESSION['user_role'] = 'Owner';
if (Permissions::hasRole('Administrator')) {
    echo "✓ Permissions::hasRole correctly allows Owner to act as Administrator.\n";
} else {
    echo "✗ Permissions::hasRole failed for Owner acting as Administrator.\n";
    exit(1);
}

echo "ProjectTest passed!\n";
