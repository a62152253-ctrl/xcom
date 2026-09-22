<?php
require_once __DIR__ . '/../config/database.php';

class NotificationService {
    public static function create($user_id, $type, $title, $message, $link = null) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            $stmt->execute([$user_id, $type, $title, $message, $link]);
            return true;
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }

    public static function notifyNewTask($user_id, $task_title, $task_id) {
        return self::create(
            $user_id,
            'new_task',
            'Nowe Zadanie',
            "Przypisano Ci nowe zadanie: {$task_title}",
            "/pages/tasks.php?id={$task_id}"
        );
    }

    public static function notifyDeadline($user_id, $task_title, $task_id) {
        return self::create(
            $user_id,
            'deadline',
            'Zbliżający się termin',
            "Termin zadania {$task_title} mija wkrótce.",
            "/pages/tasks.php?id={$task_id}"
        );
    }

    public static function notifyProjectCompleted($user_id, $project_name, $project_id) {
        return self::create(
            $user_id,
            'project_completed',
            'Projekt ukończony',
            "Projekt {$project_name} został oznaczony jako ukończony.",
            "/pages/projects.php?id={$project_id}"
        );
    }
}
