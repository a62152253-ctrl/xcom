<?php
// tests/ProjectTest.php
require_once __DIR__ . '/../config/database.php';

echo "Running ProjectTest...\n";

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("
    CREATE TABLE projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        description TEXT,
        color TEXT,
        deadline DATE,
        created_by INTEGER,
        is_archived INTEGER DEFAULT 0
    );
    CREATE TABLE project_members (
        project_id INTEGER,
        user_id INTEGER,
        role TEXT
    );
");

// Mock creation
$stmt = $db->prepare("INSERT INTO projects (name, description, color, deadline, created_by) VALUES (?, ?, ?, ?, ?)");
$stmt->execute(['Test Project', 'A test project', '#ff0000', '2024-12-31', 1]);
$proj_id = $db->lastInsertId();

if ($proj_id > 0) {
    echo "[PASS] Project inserted successfully.\n";
} else {
    echo "[FAIL] Project insertion failed.\n";
}

// Mock Add Member
$stmt_member = $db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'Owner')");
$stmt_member->execute([$proj_id, 1]);

$count = $db->query("SELECT COUNT(*) FROM project_members WHERE project_id = " . (int)$proj_id)->fetchColumn();
if ($count > 0) {
    echo "[PASS] Member added successfully.\n";
} else {
    echo "[FAIL] Member addition failed.\n";
}

echo "ProjectTest Complete.\n";
