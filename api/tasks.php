<?php
// api/tasks.php - Fixed API with proper validation & error handling
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../controllers/TaskController.php';

require_auth_api();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $result = TaskController::handleRequest($action, $method);
    if (isset($result['status'])) {
        http_response_code($result['status']);
        unset($result['status']);
    }
    echo json_encode($result);
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
