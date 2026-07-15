<?php
// includes/middleware.php - ENHANCED WITH CSRF & CSRF TOKEN VALIDATION
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';

// Ensure secure session is running
start_secure_session();

function require_auth_api() {
    if (!is_logged_in()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized. Please log in.']);
        exit;
    }
}

function require_csrf_token() {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $token = $input['csrf_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'CSRF token validation failed']);
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
    
    if (!in_array($user_role, $roles, true)) {
        http_response_code(403);
        header("Location: /pages/dashboard.php?error=unauthorized");
        exit;
    }
}

function has_project_access($project_id, $minimum_role = 'Member') {
    if (!is_logged_in()) {
        return false;
    }
    
    $project_id = (int)$project_id;
    if ($project_id <= 0) {
        return false;
    }
    
    // Admin and Owner roles bypass project checks globally
    $global_role = $_SESSION['user_role'] ?? 'Member';
    if ($global_role === 'Owner' || $global_role === 'Administrator') {
        return true;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Check if user is a member of the project
        $stmt = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$project_id, $_SESSION['user_id']]);
        $member = $stmt->fetch();
        
        if ($member) {
            $user_project_role = $member['role'];
            
            // Define role hierarchy
            $hierarchy = ['Member' => 1, 'Administrator' => 2, 'Owner' => 3];
            
            $user_weight = $hierarchy[$user_project_role] ?? 1;
            $min_weight = $hierarchy[$minimum_role] ?? 1;
            
            return $user_weight >= $min_weight;
        }
        
        // Check if user created the project
        $stmt_created = $db->prepare("SELECT created_by FROM projects WHERE id = ?");
        $stmt_created->execute([$project_id]);
        $project = $stmt_created->fetch();
        
        if ($project && (int)$project['created_by'] === (int)$_SESSION['user_id']) {
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Project access check failed: " . $e->getMessage());
        return false;
    }
}

function require_project_access($project_id, $minimum_role = 'Member') {
    if (!has_project_access($project_id, $minimum_role)) {
        http_response_code(403);
        header("Location: /pages/dashboard.php?error=project_access_denied");
        exit;
    }
}
