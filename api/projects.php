<?php
// api/projects.php
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../controllers/ProjectController.php';

require_auth_api();

header('Content-Type: application/json');

$controller = new ProjectController();
$controller->handleRequest();
