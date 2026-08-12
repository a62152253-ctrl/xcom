<?php
namespace Notifications;

use PDO;

class NotificationService {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Create a notification in the database.
     */
    private function createNotification(int $userId, string $title, string $message, string $type = 'info'): void {
        try {
            $stmt = $this->db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $message, $type]);
        } catch (\PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
        }
    }

    public function notifyNewTask(int $userId, string $taskName, string $projectName): void {
        $title = "Nowe zadanie";
        $message = "Zostałeś przypisany do nowego zadania: " . strip_tags($taskName) . " w projekcie " . strip_tags($projectName) . ".";
        $this->createNotification($userId, $title, $message, 'task_assigned');
    }

    public function notifyDeadline(int $userId, string $taskName, string $deadline): void {
        $title = "Zbliżający się termin";
        $message = "Termin zadania " . strip_tags($taskName) . " mija " . strip_tags($deadline) . ".";
        $this->createNotification($userId, $title, $message, 'deadline');
    }

    public function notifyCompletedProject(int $userId, string $projectName): void {
        $title = "Ukończony projekt";
        $message = "Wszystkie zadania w projekcie " . strip_tags($projectName) . " zostały ukończone. Dobra robota!";
        $this->createNotification($userId, $title, $message, 'project_done');
    }

    public function notifySystemInfo(int $userId, string $info): void {
        $title = "Informacja systemowa";
        $message = strip_tags($info);
        $this->createNotification($userId, $title, $message, 'system_info');
    }
}
