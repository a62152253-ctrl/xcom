<?php
// includes/middleware.php - ENHANCED WITH CSRF & CSRF TOKEN VALIDATION
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';
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
    
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'CSRF token validation failed']);
        exit;
    }
}

function require_role($roles) {
    return \Security\Permissions::requireRole($roles);
}

function has_project_access($project_id, $minimum_role = 'Member') {
    return \Security\Permissions::hasProjectAccess($project_id, $minimum_role);
}

function require_project_access($project_id, $minimum_role = 'Member') {
    return \Security\Permissions::requireProjectAccess($project_id, $minimum_role);
}
