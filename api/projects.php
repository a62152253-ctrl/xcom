<?php
// api/projects.php - Refactored API using ProjectController
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../controllers/ProjectController.php';

require_auth_api();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$response = ProjectController::handleRequest($action, $method, $input, $_GET);
echo json_encode($response);
exit;
