<?php
// includes/header.php: top of every page.
// Set these BEFORE including it:
//   $pageTitle = 'Browse materials';
//   $layout    = 'student';   // 'student' | 'admin' | 'bare' (login, register, home)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/brand.php';

$pageTitle = $pageTitle ?? 'StudyCircle';
$layout    = $layout ?? 'student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0B0F14">
  <title><?= e($pageTitle) ?> | StudyCircle</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="layout-<?= e($layout) ?>">
<a class="sc-skip" href="#main">Skip to content</a>
<?php if ($layout === 'admin'): ?>
<div class="sc-admin">
  <?php include __DIR__ . '/admin_sidebar.php'; ?>
  <main id="main" class="sc-admin-main">
    <?php show_flash(); ?>
<?php elseif ($layout === 'student'): ?>
  <?php include __DIR__ . '/student_navbar.php'; ?>
  <main id="main" class="container-xl sc-container">
    <?php show_flash(); ?>
<?php else: ?>
  <main id="main" class="sc-bare">
    <?php if (empty($hideFlash)) { show_flash(); } ?>
<?php endif; ?>
