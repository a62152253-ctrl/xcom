<?php
// api/tasks.php
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../controllers/TaskController.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $controller = new TaskController($db, $user_id);
    $controller->handleRequest($method, $action);
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}
