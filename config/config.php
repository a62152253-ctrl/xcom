<?php
// config/config.php

// Application Settings
define('APP_NAME', 'TaskManager Pro');
define('APP_VERSION', '1.0.0');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'xcom');
define('DB_PORT', '3307');

// Session Settings
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']);

// CSRF token initialization moved to start_secure_session() in includes/session.php
