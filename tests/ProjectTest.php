<?php
require_once __DIR__ . '/../config/database.php';

echo "Running ProjectTest...\n";
$db = Database::getInstance()->getConnection();

// Create test user
$email = "testproj" . time() . "@example.com";
$db->prepare("INSERT INTO users (email, password_hash, full_name, role, status) VALUES (?, 'hash', 'Test User', 'Member', 'Active')")->execute([$email]);
$userId = $db->lastInsertId();

// Create Project
$db->prepare("INSERT INTO projects (name, created_by, color) VALUES ('My Project', ?, '#123456')")->execute([$userId]);
$projectId = $db->lastInsertId();
echo "✅ Success: Project Created (ID: $projectId).\n";

// Read Project
$stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch();
if ($project && $project['name'] === 'My Project') {
    echo "✅ Success: Project Read correctly.\n";
} else {
    echo "❌ Error: Project Read failed.\n";
}

// Update Project
$db->prepare("UPDATE projects SET name = 'Renamed Project' WHERE id = ?")->execute([$projectId]);
$stmt->execute([$projectId]);
$project = $stmt->fetch();
if ($project && $project['name'] === 'Renamed Project') {
    echo "✅ Success: Project Updated correctly.\n";
} else {
    echo "❌ Error: Project Update failed.\n";
}

// Delete Project
$db->prepare("DELETE FROM projects WHERE id = ?")->execute([$projectId]);
$stmt->execute([$projectId]);
$project = $stmt->fetch();
if (!$project) {
    echo "✅ Success: Project Deleted correctly.\n";
} else {
    echo "❌ Error: Project Delete failed.\n";
}

// Cleanup
$db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
echo "ProjectTest complete.\n";
