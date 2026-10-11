<?php
// api/backup.php
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../config/env.php';

require_role(['Administrator', 'Owner']);

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i-s') . '.sql"');

$host = env('DB_HOST', 'mysql8');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$name = env('DB_NAME', 'app21');

$cmd = sprintf(
    'mysqldump --host=%s --user=%s --password=%s %s',
    escapeshellarg($host),
    escapeshellarg($user),
    escapeshellarg($pass),
    escapeshellarg($name)
);

// Execute mysqldump
passthru($cmd);
