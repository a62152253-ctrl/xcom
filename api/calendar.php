<?php
// api/calendar.php
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth_api();

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch user calendar events + tasks with deadlines as calendar events
    $start = $_GET['start'] ?? null;
    $end = $_GET['end'] ?? null;
    
    $events = [];
    
    // 1. Fetch custom events
    $stmt = $db->prepare("SELECT id, title, description, start_time as start, end_time as end, color FROM calendar_events WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $custom_events = $stmt->fetchAll();
    
    foreach ($custom_events as $ce) {
        $events[] = [
            'id' => 'custom_' . $ce['id'],
            'title' => $ce['title'],
            'description' => $ce['description'],
            'start' => $ce['start'],
            'end' => $ce['end'],
            'color' => $ce['color'],
            'allDay' => false
        ];
    }
    
    // 2. Fetch tasks with deadlines
    $stmt_tasks = $db->prepare("
        SELECT t.id, t.name, t.deadline, t.priority, p.name as project_name, p.color
        FROM tasks t
        INNER JOIN projects p ON t.project_id = p.id
        LEFT JOIN project_members pm ON p.id = pm.project_id
        WHERE (p.created_by = ? OR pm.user_id = ?) AND t.deadline IS NOT NULL
    ");
    $stmt_tasks->execute([$user_id, $user_id]);
    $tasks = $stmt_tasks->fetchAll();
    
    foreach ($tasks as $task) {
        $events[] = [
            'id' => 'task_' . $task['id'],
            'title' => '[Zadanie] ' . $task['name'],
            'description' => 'Projekt: ' . $task['project_name'] . ' | Priorytet: ' . $task['priority'],
            'start' => $task['deadline'],
            'allDay' => true,
            'color' => $task['color'] ?: '#3b82f6',
            'url' => '/pages/tasks.php?task_id=' . $task['id']
        ];
    }
    
    echo json_encode($events);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Create new custom calendar event
    if ($action === 'create') {
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $start_time = $input['start_time'] ?? null;
        $end_time = $input['end_time'] ?? null;
        $color = $input['color'] ?? '#3b82f6';
        
        if (empty($title) || !$start_time) {
            http_response_code(400);
            echo json_encode(['error' => 'Tytuł i czas rozpoczęcia są wymagane.']);
            exit;
        }
        
        $stmt = $db->prepare("INSERT INTO calendar_events (user_id, title, description, start_time, end_time, color) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $description, $start_time, $end_time ?: null, $color]);
        
        log_activity($user_id, 'calendar_event_create', "Created calendar event: " . sanitize($title));
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Drag and drop event date update
    if ($action === 'update_date') {
        $event_id = $input['id'] ?? '';
        $start = $input['start'] ?? null;
        
        if (!$start) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak daty rozpoczęcia.']);
            exit;
        }
        
        if (strpos($event_id, 'task_') === 0) {
            $task_id = (int)str_replace('task_', '', $event_id);
            
            // Check access
            $stmt_check = $db->prepare("SELECT project_id, name FROM tasks WHERE id = ?");
            $stmt_check->execute([$task_id]);
            $task = $stmt_check->fetch();
            
            if (!$task || !has_project_access($task['project_id'], 'Member')) {
                http_response_code(403);
                echo json_encode(['error' => 'Brak uprawnień.']);
                exit;
            }
            
            // Update deadline (convert start ISO format to Y-m-d)
            $date = date('Y-m-d', strtotime($start));
            $stmt = $db->prepare("UPDATE tasks SET deadline = ? WHERE id = ?");
            $stmt->execute([$date, $task_id]);
            
            log_activity($user_id, 'task_deadline_drag', "Updated deadline of task '$task[name]' to $date via calendar drag");
            echo json_encode(['success' => true]);
            exit;
        } else if (strpos($event_id, 'custom_') === 0) {
            $custom_id = (int)str_replace('custom_', '', $event_id);
            
            $start_dt = date('Y-m-d H:i:s', strtotime($start));
            $stmt = $db->prepare("UPDATE calendar_events SET start_time = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$start_dt, $custom_id, $user_id]);
            
            log_activity($user_id, 'calendar_event_drag', "Updated calendar event start time via drag");
            echo json_encode(['success' => true]);
            exit;
        }
    }
    
    // Delete custom calendar event
    if ($action === 'delete') {
        $event_id = $input['id'] ?? '';
        if (strpos($event_id, 'custom_') === 0) {
            $custom_id = (int)str_replace('custom_', '', $event_id);
            $stmt = $db->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?");
            $stmt->execute([$custom_id, $user_id]);
            
            log_activity($user_id, 'calendar_event_delete', "Deleted custom calendar event");
            echo json_encode(['success' => true]);
            exit;
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Zadania nie mogą być usunięte z poziomu kalendarza.']);
            exit;
        }
    }
}

http_response_code(400);
echo json_encode(['error' => 'Invalid Request']);
exit;
