<?php
namespace Security;

class Permissions {
    public static function hasRole($role, $allowedRoles) {
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        return in_array($role, $allowedRoles, true);
    }
}
