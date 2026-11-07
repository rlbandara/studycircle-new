<?php
// auth/logout.php: ends the session, then returns to the login page (no screen of its own).
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Start a fresh session only to show the "logged out" message
session_start();
require_once __DIR__ . '/../includes/flash.php';
set_flash('success', 'You have been logged out.');

header('Location: ' . url('auth/login.php'));
exit;
