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
    try {
        Csrf::require_csrf_token();
    } catch (Exception $e) {
        http_response_code($e->getCode() ?: 403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

function require_role($roles) {
    try {
        Permissions::require_role($roles);
    } catch (Exception $e) {
        http_response_code($e->getCode() ?: 403);
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['error' => $e->getMessage()]);
        } else {
            header("Location: /pages/dashboard.php?error=unauthorized");
        }
        exit;
    }
}

function has_project_access($project_id, $minimum_role = 'Member') {
    return Permissions::has_project_access($project_id, $minimum_role);
}

function require_project_access($project_id, $minimum_role = 'Member') {
    try {
        Permissions::require_project_access($project_id, $minimum_role);
    } catch (Exception $e) {
        http_response_code($e->getCode() ?: 403);
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['error' => $e->getMessage()]);
        } else {
            header("Location: /pages/dashboard.php?error=project_access_denied");
        }
        exit;
    }
}
