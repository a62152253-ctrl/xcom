<?php
// config/config.php

// Application Settings
define('APP_NAME', 'TaskManager Pro');
define('APP_VERSION', '1.0.0');

// Database Configuration
require_once __DIR__ . '/env.php';
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', 'root'));
define('DB_NAME', env('DB_NAME', 'xcom'));
define('DB_PORT', env('DB_PORT', '3306'));

// Session Settings
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']);

// CSRF token initialization moved to start_secure_session() in includes/session.php
