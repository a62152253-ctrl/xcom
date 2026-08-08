<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/ProjectService.php';

function test_project_service() {
    echo "Running Project Service Test...\n";
    $db = Database::getInstance()->getConnection();

    // Create mock user
    $db->prepare("INSERT INTO users (email, password_hash, full_name) VALUES ('proj_user@example.com', 'hash', 'Proj User')")->execute();
    $user_id = $db->lastInsertId();

    // Create mock project
    $db->prepare("INSERT INTO projects (name, created_by) VALUES ('Service Test Project', ?)")->execute([$user_id]);
    $project_id = $db->lastInsertId();

    // Assign member role (owner automatically happens in creation api, here we manually do it or just rely on created_by)

    $projects = ProjectService::getUserProjects($user_id);

    if (count($projects) > 0 && $projects[0]['name'] === 'Service Test Project') {
        echo "✅ Project Retrieval Passed\n";
    } else {
        echo "❌ Project Retrieval Failed\n";
    }

    // Clean up
    $db->prepare("DELETE FROM projects WHERE created_by = ?")->execute([$user_id]);
    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
}

test_project_service();
