<?php
// includes/auth_check.php: put this at the top of every student page.
// Sends guests to the login page.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/flash.php';

if (empty($_SESSION['user_id'])) {
    set_flash('warning', 'Please log in to continue.');
    header('Location: ' . url('auth/login.php'));
    exit;
}
