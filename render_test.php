<?php
$_SERVER['REQUEST_URI'] = '/pages/dashboard.php';
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'Owner';
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_email'] = 'test@example.com';
$_SESSION['user_status'] = 'Active';

$projects_count = 0;
$active_tasks_count = 0;
$done_count = 0;
$overdue_count = 0;
$tasks_today = [];
$top_projects = [];
$activity_logs = [];
$priorities_json = ['Low' => 0, 'Medium' => 0, 'High' => 0, 'Critical' => 0];

$html = file_get_contents('pages/dashboard.php');
if (strpos($html, 'Zacznijmy od konfiguracji:') !== false) {
    echo "Dashboard onboarding found\n";
}
if (strpos($html, '42h') !== false && strpos($html, '80%') !== false) {
    echo "Dashboard KPIs found\n";
}

$html2 = file_get_contents('pages/projects.php');
if (strpos($html2, 'Nie masz jeszcze projektów') !== false) {
    echo "Projects empty state found\n";
}
?>
