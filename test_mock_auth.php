<?php
require_once __DIR__ . '/includes/session.php';
start_secure_session();
$_SESSION['user_id'] = 1;
$_SESSION['user_status'] = 'Active';
$_SESSION['user_role'] = 'Administrator';
$_SESSION['user_name'] = 'Andrzej';
$_SESSION['csrf_token'] = 'test_token';
header("Location: /pages/dashboard.php");
die();
