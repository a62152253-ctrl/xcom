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
    require_once __DIR__ . '/../security/Csrf.php';
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $token = $input['csrf_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (!\Security\Csrf::validate($token)) {
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
    require_once __DIR__ . '/../security/Permissions.php';
    if (!\Security\Permissions::requireRole($_SESSION['user_id'], $roles)) {
        http_response_code(403);
        header("Location: /pages/dashboard.php?error=unauthorized");
        exit;
    }
}

function has_project_access($project_id, $minimum_role = 'Member') {
    if (!is_logged_in()) {
        return false;
    }
    require_once __DIR__ . '/../security/Permissions.php';
    return \Security\Permissions::hasProjectAccess($_SESSION['user_id'], $project_id, $minimum_role);
}

function require_project_access($project_id, $minimum_role = 'Member') {
    if (!has_project_access($project_id, $minimum_role)) {
        http_response_code(403);
        header("Location: /pages/dashboard.php?error=project_access_denied");
        exit;
    }
}
