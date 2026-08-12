<?php
namespace Security;

class Permissions {
    /**
     * Checks if the user has the required role.
     * Roles hierarchy: Owner > Administrator > Member
     */
    public static function hasRole(string $userRole, string $requiredRole): bool {
        $roles = [
            'Owner' => 3,
            'Administrator' => 2,
            'Member' => 1
        ];

        $userLevel = $roles[$userRole] ?? 0;
        $requiredLevel = $roles[$requiredRole] ?? 999;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Checks if the user can edit a task.
     * They must be either assigned to the task, or the project owner, or have Administrator+ role.
     */
    public static function canEditTask(string $userRole, int $userId, int $taskAssignedTo, int $projectCreatedBy): bool {
        if (self::hasRole($userRole, 'Administrator')) {
            return true;
        }
        if ($userId === $taskAssignedTo) {
            return true;
        }
        if ($userId === $projectCreatedBy) {
            return true;
        }
        return false;
    }
}
