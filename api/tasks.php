<?php
// api/tasks.php - Fixed API with proper validation & error handling
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

$controller = new TaskController($db, $user_id);
$controller->handleRequest($action, $method);
