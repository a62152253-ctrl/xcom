<?php
// api/backup.php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../security/Permissions.php';

start_secure_session();

if (!is_logged_in() || !Permissions::hasRole(['Owner', 'Administrator'])) {
    http_response_code(403);
    exit('Access denied');
}

$db_host = getenv('DB_HOST') ?: 'mysql8';
$db_user = getenv('DB_USER') ?: 'xcom';
$db_pass = getenv('DB_PASS') ?: 'xcom_password';
$db_name = getenv('DB_NAME') ?: 'xcom_db';

putenv("MYSQL_PWD=$db_pass");

$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename=\"$filename\"");

$command = "mysqldump -h " . escapeshellarg($db_host) . " -u " . escapeshellarg($db_user) . " " . escapeshellarg($db_name);
passthru($command);

putenv("MYSQL_PWD=");
exit;
