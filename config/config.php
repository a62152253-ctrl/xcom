<?php
// config/config.php

// Application Settings
define('APP_NAME', 'TaskManager Pro');
define('APP_VERSION', '1.0.0');

// Database Configuration
define('DB_HOST', 'mysql8');
define('DB_USER', '41958036_taskmanager');
define('DB_PASS', 'C13xQwpC');
define('DB_NAME', '41958036_taskmanager');
define('DB_PORT', '3306');

// Session Settings
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']);

// Security CSRF key
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
