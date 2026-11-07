<?php
// includes/admin_sidebar.php: left menu for admin pages (included by header.php)
$current = basename($_SERVER['SCRIPT_NAME']);
$items = [
    'Dashboard' => ['admin/dashboard.php', ['dashboard.php']],
    'Users'     => ['admin/users.php',     ['users.php']],
    'Subjects'  => ['admin/subjects.php',  ['subjects.php']],
    'Materials' => ['admin/materials.php', ['materials.php']],
    'Forum'     => ['admin/forum.php',     ['forum.php']],
    'Comments'  => ['admin/comments.php',  ['comments.php']],
    'Groups'    => ['admin/groups.php',    ['groups.php']],
    'Reports'   => ['admin/reports.php',   ['reports.php']],
];
$adminName = $_SESSION['name'] ?? 'Admin';
?>
<aside class="sc-sidebar" aria-label="Admin menu">
  <a class="sc-brand sc-brand-light" href="<?= url('admin/dashboard.php') ?>">
    <?= brand_logo('sc-logo-sky') ?>Admin
  </a>
  <nav class="sc-side-links">
    <?php foreach ($items as $label => [$href, $files]): ?>
      <a class="sc-side-link<?= in_array($current, $files, true) ? ' active' : '' ?>"
         href="<?= url($href) ?>"<?= in_array($current, $files, true) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="sc-side-foot">
    <span class="sc-avatar sc-avatar-sky" title="<?= e($adminName) ?>"><?= e(initials($adminName)) ?></span>
    <a class="btn-sc-outline" href="<?= url('auth/logout.php') ?>">Logout</a>
  </div>
</aside>
