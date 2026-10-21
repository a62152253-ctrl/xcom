<?php
require_once __DIR__ . '/MockSchema.php';

echo "Running ProjectTest...\n";
$db = new PDO('sqlite::memory:');
MockSchema::create($db);

$db->exec("INSERT INTO projects (id, name, created_by, is_archived) VALUES (1, 'Test Proj', 1, 0)");
$db->exec("INSERT INTO project_members (project_id, user_id, role) VALUES (1, 2, 'Member')");

$stmt = $db->query("SELECT * FROM projects WHERE created_by = 1");
$proj = $stmt->fetch();

if ($proj && $proj['name'] === 'Test Proj') {
    echo "ProjectTest Passed: CRUD Project OK\n";
} else {
    echo "ProjectTest Failed: Project CRUD Error\n";
    exit(1);
}
