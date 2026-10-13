<?php
namespace Notifications;
require_once __DIR__ . '/NotificationService.php';

class NotificationCenter {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function notifyNewTask($user_id, $task_name) {
        return NotificationService::create($this->db, $user_id, 'Nowe zadanie', "Zostałeś przypisany do zadania: $task_name", 'task');
    }

    public function notifyDeadline($user_id, $task_name) {
        return NotificationService::create($this->db, $user_id, 'Zbliżający się termin', "Zadanie '$task_name' ma dzisiaj termin", 'warning');
    }

    public function notifyCompletedProject($user_id, $project_name) {
        return NotificationService::create($this->db, $user_id, 'Projekt ukończony', "Projekt '$project_name' został ukończony!", 'success');
    }

    public function notifySystemInfo($user_id, $message) {
        return NotificationService::create($this->db, $user_id, 'Informacja systemowa', $message, 'info');
    }
}
