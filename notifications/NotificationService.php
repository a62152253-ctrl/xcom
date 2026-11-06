<?php
// notifications/NotificationService.php
class NotificationService {
    public static function create($db, $user_id, $title, $message, $type = 'system_info', $link = null) {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $title, $message, $type, $link]);
    }

    public static function notifyNewTask($db, $user_id, $task_id, $task_name) {
        self::create($db, $user_id, 'Nowe zadanie', "Przypisano Ci nowe zadanie: $task_name", 'new_task', "/pages/tasks.php?task_id=$task_id");
    }

    public static function notifyDeadline($db, $user_id, $task_id, $task_name) {
        self::create($db, $user_id, 'Zbliżający się termin', "Zadanie '$task_name' wkrótce wymaga ukończenia.", 'deadline', "/pages/tasks.php?task_id=$task_id");
    }

    public static function notifyProjectCompleted($db, $user_id, $project_id, $project_name) {
        self::create($db, $user_id, 'Projekt ukończony', "Projekt '$project_name' został oznaczony jako ukończony.", 'completed_project', "/pages/projects.php?id=$project_id");
    }
}
