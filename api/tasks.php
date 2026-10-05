<?php
// api/tasks.php - Fixed API with proper validation & error handling
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../controllers/TaskController.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$action = $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        TaskController::handleGet($db, $user_id, $action);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        TaskController::handlePost($db, $user_id, $action, $input);
    }
    
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}
