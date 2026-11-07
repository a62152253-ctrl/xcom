<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../security/Permissions.php';

class ProjectTest {
    public static function run() {
        echo "Running ProjectTest...\n";

        $db = Database::getInstance()->getConnection();

        // Setup test data
        $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, role, status) VALUES (?, ?, ?, 'Member', 'Active')");
        $stmt->execute(['projtest1_' . time() . '@example.com', 'hash', 'Test User 1']);
        $user1_id = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO users (email, password_hash, full_name, role, status) VALUES (?, ?, ?, 'Member', 'Active')");
        $stmt->execute(['projtest2_' . time() . '@example.com', 'hash', 'Test User 2']);
        $user2_id = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO projects (name, created_by) VALUES (?, ?)");
        $stmt->execute(['Test Project', $user1_id]);
        $project_id = $db->lastInsertId();

        // User 1 should have access (created it)
        // Mock session for Permissions check
        $_SESSION['user_id'] = $user1_id;
        $_SESSION['user_role'] = 'Member';
        $_SESSION['user_status'] = 'Active';

        if (Permissions::hasProjectAccess($project_id)) {
            echo "✅ Project creator access passed\n";
        } else {
            echo "❌ Project creator access failed\n";
        }

        // User 2 should NOT have access initially
        $_SESSION['user_id'] = $user2_id;

        if (!Permissions::hasProjectAccess($project_id)) {
            echo "✅ Non-member access denial passed\n";
        } else {
            echo "❌ Non-member access denial failed\n";
        }

        // Add User 2 as member
        $stmt = $db->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'Member')");
        $stmt->execute([$project_id, $user2_id]);

        // User 2 should NOW have access
        if (Permissions::hasProjectAccess($project_id)) {
            echo "✅ Member access passed\n";
        } else {
            echo "❌ Member access failed\n";
        }

        // Cleanup
        $db->prepare("DELETE FROM project_members WHERE project_id = ?")->execute([$project_id]);
        $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$project_id]);
        $db->prepare("DELETE FROM users WHERE id IN (?, ?)")->execute([$user1_id, $user2_id]);
        unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_status']);
    }
}

ProjectTest::run();
