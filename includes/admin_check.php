<?php
// includes/admin_check.php: put this at the top of every admin page.
// Blocks guests and normal students.
require_once __DIR__ . '/auth_check.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    set_flash('danger', 'You do not have permission to open that page.');
    header('Location: ' . url('student/dashboard.php'));
    exit;
}
