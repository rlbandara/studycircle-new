<?php
// includes/student_navbar.php: top navigation for student pages (included by header.php)
$current = basename($_SERVER['SCRIPT_NAME']);
$items = [
    'Dashboard' => ['student/dashboard.php', ['dashboard.php']],
    'Materials' => ['student/materials.php', ['materials.php', 'material_view.php', 'material_form.php', 'my_materials.php']],
    'Forum'     => ['student/forum.php',     ['forum.php', 'question_form.php', 'question_view.php']],
    'Groups'    => ['student/groups.php',    ['groups.php', 'group_form.php', 'group_view.php', 'session_form.php']],
    'Profile'   => ['student/profile.php',   ['profile.php']],
];
$userName = $_SESSION['name'] ?? 'Student';
?>
<nav class="navbar navbar-expand-lg sc-nav">
  <div class="container-xl sc-nav-inner">
    <a class="sc-brand" href="<?= url('student/dashboard.php') ?>">
      <?= brand_logo() ?>StudyCircle
    </a>
    <button class="navbar-toggler sc-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#scMenu" aria-controls="scMenu" aria-expanded="false" aria-label="Open menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="scMenu">
      <ul class="navbar-nav me-auto gap-1 sc-nav-links">
        <?php foreach ($items as $label => [$href, $files]): ?>
          <li class="nav-item">
            <a class="sc-nav-link<?= in_array($current, $files, true) ? ' active' : '' ?>"
               href="<?= url($href) ?>"<?= in_array($current, $files, true) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="sc-nav-user">
        <span class="sc-avatar" title="<?= e($userName) ?>"><?= e(initials($userName)) ?></span>
        <a class="btn-sc-outline" href="<?= url('auth/logout.php') ?>">Logout</a>
      </div>
    </div>
  </div>
</nav>
