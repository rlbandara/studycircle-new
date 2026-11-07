<?php
// index.php: public home page.
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loggedIn  = !empty($_SESSION['user_id']);
$dashboard = url(($_SESSION['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');

$students  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$materials = (int)$pdo->query("SELECT COUNT(*) FROM materials WHERE status = 'approved'")->fetchColumn();
$questions = (int)$pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();

$latest = $pdo->query(
    "SELECT m.title, m.downloads, m.subject_id, s.name AS subject, u.name AS uploader
       FROM materials m JOIN subjects s ON s.id = m.subject_id JOIN users u ON u.id = m.user_id
      WHERE m.status = 'approved' ORDER BY m.created_at DESC LIMIT 3"
)->fetchAll();

$pageTitle = 'Learn together';
$layout    = 'bare';
$hideFlash = true;
include __DIR__ . '/includes/header.php';
?>
<header class="landing-top">
  <div class="container-xl d-flex justify-content-between align-items-center py-3 gap-3 flex-wrap">
    <a class="sc-brand" href="<?= url('') ?>"><?= brand_logo() ?>StudyCircle</a>
    <div class="d-flex gap-2">
      <?php if ($loggedIn): ?>
        <a class="btn-sc" href="<?= $dashboard ?>">Go to dashboard</a>
      <?php else: ?>
        <a class="btn-sc-outline" href="<?= url('auth/login.php') ?>">Login</a>
        <a class="btn-sc" href="<?= url('auth/register.php') ?>">Register</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<section class="landing-hero<?= has_campus_photo() ? ' has-campus' : '' ?>">
  <div class="container-xl">
    <div class="row align-items-center g-4 gx-lg-5">
      <div class="col-lg-6">
        <span class="eyebrow">NSBM Green University</span>
        <h1>Learn together. Share notes. <span>Pass together.</span></h1>
        <p>The NSBM student community for lecture notes, questions and study groups.</p>
        <div class="d-flex gap-2 flex-wrap mt-4">
          <?php if ($loggedIn): ?>
            <a class="btn-sc" href="<?= url('student/materials.php') ?>">Browse materials</a>
          <?php else: ?>
            <a class="btn-sc" href="<?= url('auth/register.php') ?>">Create a free account</a>
            <a class="btn-sc-outline" href="<?= url('auth/login.php') ?>">Login</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-6">
        <img class="hero-media" src="<?= url('assets/img/hero-study.svg') ?>" width="640" height="480" alt="Students sharing notes and studying together">
      </div>
    </div>
  </div>
</section>

<div class="landing-body<?= has_campus_photo() ? ' has-campus' : '' ?>">
<div class="container-xl py-5">
  <div class="row g-3 mb-5">
    <?php foreach ([[$students, 'Students'], [$materials, 'Shared materials'], [$questions, 'Questions asked']] as [$n, $label]): ?>
      <div class="col-md-4"><div class="card-sc stat-card">
        <span class="stat-icon" aria-hidden="true"></span>
        <div><div class="stat-number"><?= number_format($n) ?></div><div class="text-muted-sc"><?= e($label) ?></div></div>
      </div></div>
    <?php endforeach; ?>
  </div>

  <div class="section-heading"><h2>Everything your batch needs</h2></div>
  <div class="row g-4 mb-5">
    <?php foreach ([
        ['Share notes', 'Upload your lecture notes and download what your batch has shared, sorted by subject.', 'feature-notes.svg'],
        ['Ask and answer', 'Post academic questions and get answers from students who have been there.', 'feature-forum.svg'],
        ['Study together', 'Join a study group for your module and plan study sessions with your friends.', 'feature-groups.svg'],
    ] as [$t, $d, $img]): ?>
      <div class="col-md-4"><article class="card-sc card-sc-flush feature-card h-100">
        <div class="card-media"><img src="<?= url('assets/img/' . $img) ?>" alt="" loading="lazy"></div>
        <div class="p-4"><h2><?= e($t) ?></h2><p class="mb-0 text-muted-sc"><?= e($d) ?></p></div>
      </article></div>
    <?php endforeach; ?>
  </div>

  <?php if ($latest): ?>
    <div class="section-heading"><h2>Latest shared materials</h2></div>
    <div class="row g-4">
      <?php foreach ($latest as $m): ?>
        <div class="col-md-4"><article class="card-sc card-sc-flush h-100"><div class="card-strip"></div>
          <div class="p-4 d-flex flex-column gap-2">
            <div><?= subject_badge($m['subject'], (int)$m['subject_id']) ?></div>
            <h3 class="h5 mb-0"><?= e($m['title']) ?></h3>
            <div class="list-row-meta">By <?= e($m['uploader']) ?>, <?= (int)$m['downloads'] ?> downloads</div>
            <a href="<?= $loggedIn ? url('student/materials.php') : url('auth/login.php') ?>"><?= $loggedIn ? 'Browse materials' : 'Login to download' ?></a>
          </div></article></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!$loggedIn): ?>
    <div class="cta-band mt-5">
      <div><h2>Ready to study smarter?</h2><p>Join your batch on StudyCircle in under a minute.</p></div>
      <a class="btn-sc" href="<?= url('auth/register.php') ?>">Create a free account</a>
    </div>
  <?php endif; ?>
</div>

</div>

<footer class="sc-footer"><div class="container-xl">&copy; <?= date('Y') ?> StudyCircle, NSBM Green University</div></footer>
<?php include __DIR__ . '/includes/footer.php'; ?>
