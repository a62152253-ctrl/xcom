<?php
require_once __DIR__ . '/../security/Permissions.php';

echo "Testing Permissions:\n";

if (class_exists('Permissions')) {
    echo "✔ Permissions class exists.\n";
} else {
    echo "❌ Permissions class does not exist.\n";
}

if (method_exists('Permissions', 'hasProjectAccess')) {
    echo "✔ Permissions::hasProjectAccess method exists.\n";
} else {
    echo "❌ Permissions::hasProjectAccess method does not exist.\n";
}

// Without DB mocking, we just test a negative non-DB case
if (!Permissions::hasProjectAccess(1, 0)) {
    echo "✔ hasProjectAccess() fails correctly for invalid project_id 0.\n";
} else {
    echo "❌ hasProjectAccess() should return false for project_id 0.\n";
}
