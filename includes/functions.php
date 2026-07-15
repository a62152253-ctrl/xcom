<?php
// includes/functions.php
require_once __DIR__ . '/../config/database.php';

// Prevent XSS - enhanced
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Input validation
function validate_input($input, $type = 'string', $max_length = 255) {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) ? $input : null;
        case 'integer':
            return filter_var($input, FILTER_VALIDATE_INT) !== false ? (int)$input : null;
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL) ? $input : null;
        case 'string':
        default:
            $clean = trim($input);
            return strlen($clean) <= $max_length ? $clean : null;
    }
}

// CSRF check
function validate_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

// Log activity to database with security context
function log_activity($user_id, $action, $details = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
        $stmt->execute([$user_id, $action, $details, $ip, $ua]);
    } catch (PDOException $e) {
        error_log("Activity log failed: " . $e->getMessage());
    }
}

// Create notification
function create_notification($user_id, $title, $message, $type = 'info') {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $message, $type]);
    } catch (PDOException $e) {
        error_log("Notification creation failed: " . $e->getMessage());
    }
}

// Send email (simple wrapper, requires SMTP setup)
function send_email($to, $subject, $message, $headers = null) {
    if (!$headers) {
        $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . (getenv('MAIL_FROM') ?: 'noreply@example.com') . "\r\n";
    }
    return mail($to, $subject, $message, $headers);
}

// Global translations dictionary
$translations = [
    'pl' => [
        'dashboard' => 'Panel główny',
        'projects' => 'Projekty',
        'tasks' => 'Zadania',
        'calendar' => 'Kalendarz',
        'profile' => 'Profil',
        'settings' => 'Ustawienia',
        'admin' => 'Panel Admina',
        'logout' => 'Wyloguj się',
        'todo' => 'Do zrobienia',
        'in_progress' => 'W toku',
        'review' => 'Weryfikacja',
        'done' => 'Zrobione',
        'low' => 'Niski',
        'medium' => 'Średni',
        'high' => 'Wysoki',
        'critical' => 'Krytyczny',
        'active_tasks' => 'Aktywne zadania',
        'tasks_today' => 'Na dziś',
        'tasks_overdue' => 'Po terminie',
        'upcoming_events' => 'Nadchodzące wydarzenia',
        'recent_activity' => 'Ostatnia aktywność',
        'notifications' => 'Powiadomienia',
        'productivity_chart' => 'Wykres produktywności',
        'theme' => 'Motyw',
        'language' => 'Język',
        'save' => 'Zapisz',
        'add_task' => 'Dodaj zadanie',
        'add_project' => 'Dodaj projekt',
        'name' => 'Nazwa',
        'description' => 'Opis',
        'deadline' => 'Termin',
        'priority' => 'Priorytet',
        'status' => 'Status',
        'assigned' => 'Przypisany',
        'actions' => 'Akcje'
    ],
    'en' => [
        'dashboard' => 'Dashboard',
        'projects' => 'Projects',
        'tasks' => 'Tasks',
        'calendar' => 'Calendar',
        'profile' => 'Profile',
        'settings' => 'Settings',
        'admin' => 'Admin Panel',
        'logout' => 'Logout',
        'todo' => 'To Do',
        'in_progress' => 'In Progress',
        'review' => 'Review',
        'done' => 'Done',
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
        'active_tasks' => 'Active Tasks',
        'tasks_today' => 'Due Today',
        'tasks_overdue' => 'Overdue',
        'upcoming_events' => 'Upcoming Events',
        'recent_activity' => 'Recent Activity',
        'notifications' => 'Notifications',
        'productivity_chart' => 'Productivity Chart',
        'theme' => 'Theme',
        'language' => 'Language',
        'save' => 'Save',
        'add_task' => 'Add Task',
        'add_project' => 'Add Project',
        'name' => 'Name',
        'description' => 'Description',
        'deadline' => 'Deadline',
        'priority' => 'Priority',
        'status' => 'Status',
        'assigned' => 'Assigned To',
        'actions' => 'Actions'
    ]
];

// Helper to translate strings
function __($key) {
    global $translations;
    $lang = $_SESSION['user_language'] ?? 'pl';
    return $translations[$lang][$key] ?? $translations['pl'][$key] ?? $key;
}
