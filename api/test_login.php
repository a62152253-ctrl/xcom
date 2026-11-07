<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'test@example.com';
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_role'] = 'Owner';
$_SESSION['user_status'] = 'Active';
$_SESSION['user_language'] = 'pl';
$_SESSION['user_theme'] = 'dark';
echo "OK";
