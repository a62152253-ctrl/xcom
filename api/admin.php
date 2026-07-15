<?php
// api/admin.php
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

// Allow only Administrator and Owner
require_role(['Owner', 'Administrator']);

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

$action = $_GET['action'] ?? '';

// Backup Action
if ($action === 'backup') {
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i-s') . '.sql"');
    
    // Simple table dump exporter
    $tables = [
        'users', 'settings', 'projects', 'project_members', 
        'tasks', 'task_comments', 'task_files', 'subtasks', 
        'notifications', 'calendar_events', 'activity_logs', 'password_resets'
    ];
    
    echo "-- Database backup of " . DB_NAME . "\n";
    echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        // Drop statement
        echo "DROP TABLE IF EXISTS `$table`;\n";
        
        // Create table statement
        $create_stmt = $db->query("SHOW CREATE TABLE `$table`")->fetch();
        echo $create_stmt['Create Table'] . ";\n\n";
        
        // Insert values
        $rows = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            echo "INSERT INTO `$table` VALUES \n";
            $val_strs = [];
            foreach ($rows as $row) {
                $escaped = array_map(function($val) use ($db) {
                    if ($val === null) return 'NULL';
                    return $db->quote($val);
                }, $row);
                $val_strs[] = "(" . implode(", ", $escaped) . ")";
            }
            echo implode(",\n", $val_strs) . ";\n\n";
        }
    }
    
    log_activity($user_id, 'admin_backup', 'Database backup downloaded');
    exit;
}

// REST actions (AJAX)
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Block / Unblock user
    if ($action === 'toggle_status') {
        $target_user_id = $input['user_id'] ?? null;
        $status = $input['status'] ?? 'Active';
        
        if (!$target_user_id || !in_array($status, ['Active', 'Blocked'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowe dane.']);
            exit;
        }
        
        // Check hierarchy: cannot block Owner or self
        $stmt_target = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt_target->execute([$target_user_id]);
        $target_role = $stmt_target->fetchColumn();
        
        if ($target_user_id == $user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Nie możesz zablokować samego siebie.']);
            exit;
        }
        
        if ($target_role === 'Owner') {
            http_response_code(400);
            echo json_encode(['error' => 'Nie możesz zablokować właściciela systemu.']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $target_user_id]);
        
        log_activity($user_id, 'admin_user_status', "Set user ID $target_user_id status to $status");
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Change User Role
    if ($action === 'change_role') {
        $target_user_id = $input['user_id'] ?? null;
        $role = $input['role'] ?? 'Member';
        
        if (!$target_user_id || !in_array($role, ['Owner', 'Administrator', 'Member'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowe dane.']);
            exit;
        }
        
        // Only Owner can change roles to Administrator/Owner
        if ($_SESSION['user_role'] !== 'Owner' && ($role === 'Owner' || $role === 'Administrator')) {
            http_response_code(403);
            echo json_encode(['error' => 'Tylko Właściciel może mianować administratorów.']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $target_user_id]);
        
        log_activity($user_id, 'admin_user_role', "Changed user ID $target_user_id role to $role");
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error' => 'Błędne zapytanie']);
exit;
