<?php
// api/backup.php - Database Backup Feature
require_once __DIR__ . '/../includes/middleware.php';

require_auth_api();

// Only Owner can generate backups
require_role('Owner');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

require_csrf_token();

try {
    $db_host = getenv('DB_HOST');
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASS');
    $db_name = getenv('DB_NAME');

    if (!$db_host || !$db_user || !$db_pass || !$db_name) {
        http_response_code(500);
        echo json_encode(['error' => 'Brak konfiguracji bazy danych dla backupu.']);
        exit;
    }

    $backup_file = sys_get_temp_dir() . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

    // Execute mysqldump securely by passing the password via environment variable
    $command = sprintf(
        'MYSQL_PWD=%s mysqldump -h %s -u %s %s > %s 2>&1',
        escapeshellarg($db_pass),
        escapeshellarg($db_host),
        escapeshellarg($db_user),
        escapeshellarg($db_name),
        escapeshellarg($backup_file)
    );

    // Use putenv to set the password so it's not exposed in `ps` output
    putenv('MYSQL_PWD=' . $db_pass);
    exec(sprintf(
        'mysqldump -h %s -u %s %s > %s 2>&1',
        escapeshellarg($db_host),
        escapeshellarg($db_user),
        escapeshellarg($db_name),
        escapeshellarg($backup_file)
    ), $output, $return_var);
    putenv('MYSQL_PWD=');

    if ($return_var === 0 && file_exists($backup_file)) {
        $content = file_get_contents($backup_file);
        unlink($backup_file);

        log_activity($_SESSION['user_id'], 'backup_generate', 'Generated database backup.');

        // Clear JSON headers and force download
        header_remove('Content-Type');
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i-s') . '.sql"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($content));

        echo $content;
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Wystąpił błąd podczas generowania kopii zapasowej.']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Wystąpił błąd serwera: ' . $e->getMessage()]);
    exit;
}
