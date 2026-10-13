<?php
// includes/middleware.php - ENHANCED WITH CSRF & CSRF TOKEN VALIDATION
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../security/Csrf.php';
require_once __DIR__ . '/../security/Permissions.php';

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
    
    if (!\Security\Csrf::validateToken($token)) {
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
    
    if (!\Security\Permissions::requireRole($roles, $user_role)) {
        http_response_code(403);
        header("Location: /pages/dashboard.php?error=unauthorized");
        exit;
    }
}

function has_project_access($project_id, $minimum_role = 'Member') {
    if (!is_logged_in()) {
        return false;
    }
    
    $db = Database::getInstance()->getConnection();
    return \Security\Permissions::hasProjectAccess(
        $db,
        $project_id,
        $_SESSION['user_id'],
        $_SESSION['user_role'] ?? 'Member',
        $minimum_role
    );
}

function require_project_access($project_id, $minimum_role = 'Member') {
    if (!has_project_access($project_id, $minimum_role)) {
        http_response_code(403);
        header("Location: /pages/dashboard.php?error=project_access_denied");
        exit;
    }
}
