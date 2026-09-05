<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../controllers/DashboardController.php';

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

$dashboardData = getDashboardData($db, $user_id);
extract($dashboardData);

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Dzień dobry' : ($hour < 18 ? 'Cześć' : 'Dobry wieczór');
?>

<?php require_once __DIR__ . '/../includes/components/dashboard_hero.php'; ?>
<?php require_once __DIR__ . '/../includes/components/dashboard_kpi.php'; ?>
<?php require_once __DIR__ . '/../includes/components/dashboard_tasks.php'; ?>
<?php require_once __DIR__ . '/../includes/components/dashboard_projects_activity.php'; ?>

<script>
    window.dashboardPriorities = [<?= implode(',', array_values($priorities_json)) ?>];
</script>
<script src="/assets/js/dashboard.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
