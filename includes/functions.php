<?php
// includes/functions.php
require_once __DIR__ . '/../config/database.php';

// Prevent XSS - SonarQube safe
function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
    require_once __DIR__ . '/../security/Csrf.php';
    return \Security\Csrf::validate($token);
}

// Log activity to database with security context
function log_activity($user_id, $action, $details = null) {
    require_once __DIR__ . '/../security/SecurityAudit.php';
    \Security\SecurityAudit::logActivity($user_id, $action, $details);
}

// Create notification
function create_notification($user_id, $title, $message, $type = 'info') {
    require_once __DIR__ . '/../notifications/NotificationService.php';
    \Notifications\NotificationService::notify($user_id, $title, $message, $type);
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
