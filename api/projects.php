<?php
// api/projects.php
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../controllers/ProjectController.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ProjectController::handleGet($db, $user_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    ProjectController::handlePost($db, $user_id, $action, $input);
}

http_response_code(400);
echo json_encode(['error' => 'Błędne zapytanie']);
exit;
