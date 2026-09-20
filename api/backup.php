<?php
// api/backup.php - Admin endpoint for database backups

require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();
require_admin_api(); // Make sure user is admin (you can adjust permission)

$db = Database::getInstance()->getConnection();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create an actual backup using mysqldump
        $backup_file = 'backup_' . date('Ymd_His') . '.sql';
        $backup_path = __DIR__ . '/../uploads/' . $backup_file;

        $db_host = getenv('DB_HOST');
        $db_user = getenv('DB_USER');
        $db_pass = getenv('DB_PASS');
        $db_name = getenv('DB_NAME');

        $command = "mysqldump -h " . escapeshellarg($db_host) . " -u " . escapeshellarg($db_user) . " -p" . escapeshellarg($db_pass) . " " . escapeshellarg($db_name) . " > " . escapeshellarg($backup_path) . " 2>&1";

        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);

        if ($return_var === 0 && file_exists($backup_path) && filesize($backup_path) > 0) {
            log_activity($_SESSION['user_id'], 'Automatyczny backup bazy danych utworzony', $backup_file);

            echo json_encode([
                'success' => true,
                'message' => 'Kopia zapasowa bazy danych została pomyślnie utworzona.',
                'file' => $backup_file
            ]);
            exit;
        } else {
            throw new Exception("Błąd mysqldump: " . implode("\n", $output));
        }
    } catch (Exception $e) {
        error_log("Backup failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Błąd podczas tworzenia kopii zapasowej.',
            'details' => $e->getMessage()
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
