<?php
// index.php
require_once __DIR__ . '/includes/session.php';

start_secure_session();

if (is_logged_in()) {
    header("Location: /pages/dashboard.php");
} else {
    header("Location: /auth/login.php");
}
exit;
