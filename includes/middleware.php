<?php
// includes/middleware.php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';

// Ensure secure session is running
start_secure_session();

function require_auth_api() {
    if (!is_logged_in()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized. Please log in.']);
        exit;
    }
}

function require_role($roles) {
    if (!is_logged_in()) {
        header("Location: /auth/login.php");
        exit;
    }
    
    $user_role = $_SESSION['user_role'] ?? 'Member';
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array($user_role, $roles)) {
        header("Location: /pages/dashboard.php?error=unauthorized");
        exit;
    }
}

function has_project_access($project_id, $minimum_role = 'Member') {
    if (!is_logged_in()) {
        return false;
    }
    
    // Admin and Owner roles bypass project checks globally
    $global_role = $_SESSION['user_role'] ?? 'Member';
    if ($global_role === 'Owner' || $global_role === 'Administrator') {
        return true;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Check if user is a member of the project
    $stmt = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $_SESSION['user_id']]);
    $member = $stmt->fetch();
    
    if (!$member) {
        // Check if user created the project
        $stmt_created = $db->prepare("SELECT created_by FROM projects WHERE id = ?");
        $stmt_created->execute([$project_id]);
        $project = $stmt_created->fetch();
        if ($project && $project['created_by'] == $_SESSION['user_id']) {
            return true;
        }
        return false;
    }
    
    $user_project_role = $member['role'];
    
    // Define role hierarchy: Owner > Administrator > Member
    $hierarchy = ['Member' => 1, 'Administrator' => 2, 'Owner' => 3];
    
    $user_weight = $hierarchy[$user_project_role] ?? 1;
    $min_weight = $hierarchy[$minimum_role] ?? 1;
    
    return $user_weight >= $min_weight;
}

function require_project_access($project_id, $minimum_role = 'Member') {
    if (!has_project_access($project_id, $minimum_role)) {
        header("Location: /pages/dashboard.php?error=project_access_denied");
        exit;
    }
}
