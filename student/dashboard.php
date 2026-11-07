<?php
// student/dashboard.php: overview for a logged-in student.
require_once __DIR__ . '/../includes/auth_check.php';

$userId = (int)$_SESSION['user_id'];

// ----- Stat cards -----
$stmt = $pdo->prepare('SELECT COUNT(*) FROM materials WHERE user_id = ?');
$stmt->execute([$userId]);
$myUploads = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(downloads), 0) FROM materials WHERE user_id = ? AND status = 'approved'");
$stmt->execute([$userId]);
$myDownloads = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM questions WHERE user_id = ?');
$stmt->execute([$userId]);
$myQuestions = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM group_members WHERE user_id = ?');
$stmt->execute([$userId]);
$myGroups = (int)$stmt->fetchColumn();

// ----- My latest uploads -----
$stmt = $pdo->prepare(
    'SELECT m.id, m.title, m.status, s.name AS subject
       FROM materials m JOIN subjects s ON s.id = m.subject_id
      WHERE m.user_id = ? ORDER BY m.created_at DESC LIMIT 5'
);
$stmt->execute([$userId]);
$recent = $stmt->fetchAll();

// ----- Upcoming sessions in my groups -----
$stmt = $pdo->prepare(
    'SELECT ss.title, ss.session_date, ss.location, g.name AS group_name
       FROM study_sessions ss
       JOIN study_groups g   ON g.id = ss.group_id
       JOIN group_members gm ON gm.group_id = g.id AND gm.user_id = ?
      WHERE ss.session_date >= NOW()
      ORDER BY ss.session_date ASC LIMIT 5'
);
$stmt->execute([$userId]);
$sessions = $stmt->fetchAll();

$statusBadge = ['approved' => 'badge-sc-success', 'pending' => 'badge-sc-amber', 'rejected' => 'badge-sc-danger'];
$firstName   = explode(' ', trim($_SESSION['name'] ?? 'there'))[0];

$pageTitle = 'Dashboard';
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="hero-banner">
  <div class="hero-banner-text">
    <h1>Welcome back, <?= e($firstName) ?></h1>
    <div class="mb-3">Share a note today and help your batch.</div>
    <a class="btn-sc-outline" href="<?= url('student/material_form.php') ?>">Upload material</a>
  </div>
  <img class="hero-banner-img" src="<?= url('assets/img/student-study.svg') ?>" width="360" height="250" alt="A child studying at a desk">
</div>

<div class="row g-3 mb-4">
  <?php
  $stats = [
      [$myUploads,   'My uploads'],
      [$myDownloads, 'Downloads of my notes'],
      [$myQuestions, 'Questions asked'],
      [$myGroups,    'Study groups'],
  ];
  foreach ($stats as [$num, $label]): ?>
    <div class="col-6 col-lg-3">
      <div class="card-sc stat-card">
        <span class="stat-icon" aria-hidden="true"></span>
        <div>
          <div class="stat-number"><?= number_format($num) ?></div>
          <div class="text-muted-sc"><?= e($label) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <section class="card-sc h-100">
      <h2 class="section-title">My recent notes</h2>
      <?php if (!$recent): ?>
        <div class="empty-state">You have not uploaded anything yet. <a href="<?= url('student/material_form.php') ?>">Upload your first note</a>.</div>
      <?php endif; ?>
      <?php foreach ($recent as $m): ?>
        <div class="list-row">
          <div>
            <div class="list-row-title"><?= e($m['title']) ?></div>
            <div class="list-row-meta"><?= e($m['subject']) ?></div>
          </div>
          <span class="badge-sc <?= $statusBadge[$m['status']] ?? 'badge-sc' ?>"><?= e(ucfirst($m['status'])) ?></span>
        </div>
      <?php endforeach; ?>
      <?php if ($recent): ?>
        <div class="mt-3"><a href="<?= url('student/my_materials.php') ?>">See all my uploads</a></div>
      <?php endif; ?>
    </section>
  </div>

  <div class="col-lg-5">
    <section class="card-sc h-100">
      <h2 class="section-title">Upcoming sessions</h2>
      <?php if (!$sessions): ?>
        <div class="empty-state">No sessions planned. <a href="<?= url('student/groups.php') ?>">Find a study group</a>.</div>
      <?php endif; ?>
      <?php foreach ($sessions as $s): ?>
        <div class="list-row">
          <div>
            <div class="list-row-title"><?= e($s['title']) ?></div>
            <div class="list-row-meta"><?= e(date('D j M, g:i A', strtotime($s['session_date']))) ?><?= $s['location'] ? ', ' . e($s['location']) : '' ?></div>
          </div>
          <span class="badge-sc badge-sc-sky"><?= e($s['group_name']) ?></span>
        </div>
      <?php endforeach; ?>
    </section>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
