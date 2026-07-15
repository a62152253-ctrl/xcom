<?php
// includes/validators.php - Input validation helpers
class InputValidator {
    public static function email(string $value): ?string {
        $value = trim($value);
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    public static function string(string $value, int $min = 1, int $max = 255): ?string {
        $value = trim($value);
        $len = strlen($value);
        return ($len >= $min && $len <= $max) ? $value : null;
    }

    public static function integer($value, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): ?int {
        $val = filter_var($value, FILTER_VALIDATE_INT);
        if ($val === false) return null;
        return ($val >= $min && $val <= $max) ? $val : null;
    }

    public static function url(string $value): ?string {
        return filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }

    public static function date(string $value): ?string {
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return ($d && $d->format('Y-m-d') === $value) ? $value : null;
    }

    public static function enum(string $value, array $allowed): ?string {
        return in_array($value, $allowed, true) ? $value : null;
    }

    public static function color(string $value): ?string {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : null;
    }

    public static function password(string $value): ?string {
        return strlen($value) >= 8 ? $value : null;
    }

    public static function validateProjectData(array $data): array {
        $errors = [];
        if (empty($data['name']) || strlen($data['name']) > 255) $errors[] = 'Invalid project name';
        if (isset($data['color']) && !self::color($data['color'])) $errors[] = 'Invalid color';
        if (isset($data['deadline']) && $data['deadline'] && !self::date($data['deadline'])) $errors[] = 'Invalid deadline';
        return $errors;
    }

    public static function validateTaskData(array $data): array {
        $errors = [];
        if (empty($data['name']) || strlen($data['name']) > 255) $errors[] = 'Invalid task name';
        if (isset($data['priority']) && !self::enum($data['priority'], ['Low', 'Medium', 'High', 'Critical'])) $errors[] = 'Invalid priority';
        if (isset($data['status']) && !self::enum($data['status'], ['To Do', 'In Progress', 'Review', 'Done'])) $errors[] = 'Invalid status';
        if (isset($data['deadline']) && $data['deadline'] && !self::date($data['deadline'])) $errors[] = 'Invalid deadline';
        return $errors;
    }
}
