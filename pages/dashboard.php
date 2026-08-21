<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../controllers/DashboardController.php';

$dashboardData = DashboardController::getDashboardData();

// Extract variables for components
extract($dashboardData);

require_once __DIR__ . '/../includes/components/dashboard_hero.php';
require_once __DIR__ . '/../includes/components/dashboard_kpis.php';
require_once __DIR__ . '/../includes/components/dashboard_main.php';

require_once __DIR__ . '/../includes/footer.php';
