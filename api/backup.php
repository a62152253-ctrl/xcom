<?php
// api/backup.php
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../config/env.php';

// Only owner can backup
require_role('Owner');

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i-s') . '.sql"');

$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$name = env('DB_NAME', 'xcom');

// Securely use mysqldump
putenv("MYSQL_PWD={$pass}");
$cmd = "mysqldump -h " . escapeshellarg($host) . " -u " . escapeshellarg($user) . " " . escapeshellarg($name);
passthru($cmd);
putenv("MYSQL_PWD="); // Clear it immediately
