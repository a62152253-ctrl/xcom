<?php
namespace Notifications;

class NotificationCenter {
    // Manages routing and complex logic for notifications if needed in the future
    public static function notifyUser($user_id, $title, $message, $type = 'info') {
        NotificationService::send($user_id, $title, $message, $type);
    }
}
