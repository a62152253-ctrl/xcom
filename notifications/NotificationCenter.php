<?php
namespace Notifications;

use PDO;
require_once __DIR__ . '/NotificationService.php';

/**
 * Central point for managing app notifications.
 */
class NotificationCenter {
    private NotificationService $service;

    public function __construct(PDO $db) {
        $this->service = new NotificationService($db);
    }

    public function sendNewTaskNotification(int $userId, string $taskName, string $projectName): void {
        $this->service->notifyNewTask($userId, $taskName, $projectName);
    }

    public function sendDeadlineNotification(int $userId, string $taskName, string $deadline): void {
        $this->service->notifyDeadline($userId, $taskName, $deadline);
    }

    public function sendCompletedProjectNotification(int $userId, string $projectName): void {
        $this->service->notifyCompletedProject($userId, $projectName);
    }

    public function sendSystemInfoNotification(int $userId, string $info): void {
        $this->service->notifySystemInfo($userId, $info);
    }
}
