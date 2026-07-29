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

require_once __DIR__ . '/../security/Csrf.php';
require_once __DIR__ . '/../security/Permissions.php';
require_once __DIR__ . '/../security/SecurityAudit.php';

function require_csrf_token() {
    \Security\Csrf::requireToken();
}

function require_role($roles) {
    \Security\Permissions::requireRole($roles);
}

function has_project_access($project_id, $minimum_role = 'Member') {
    if (!is_logged_in()) return false;
    return \Security\Permissions::hasProjectAccess($project_id, $minimum_role);
}

function require_project_access($project_id, $minimum_role = 'Member') {
    \Security\Permissions::requireProjectAccess($project_id, $minimum_role);
}
