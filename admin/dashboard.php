<?php
// admin/dashboard.php: overview for the administrator.
require_once __DIR__ . '/../includes/admin_check.php';

$count = function (string $sql) use ($pdo): int {
    return (int)$pdo->query($sql)->fetchColumn();
};

// ----- Stat cards -----
$totalUsers    = $count("SELECT COUNT(*) FROM users WHERE role = 'student'");
$totalApproved = $count("SELECT COUNT(*) FROM materials WHERE status = 'approved'");
$totalDownload = $count('SELECT COALESCE(SUM(downloads), 0) FROM materials');
$pendingCount  = $count("SELECT COUNT(*) FROM materials WHERE status = 'pending'");

// ----- Chart: approved uploads per subject -----
$rows = $pdo->query(
    "SELECT s.name, COUNT(m.id) AS total
       FROM subjects s LEFT JOIN materials m ON m.subject_id = s.id AND m.status = 'approved'
      GROUP BY s.id, s.name ORDER BY s.name"
)->fetchAll();
$chartLabels = array_column($rows, 'name');
$chartValues = array_map('intval', array_column($rows, 'total'));

// ----- Pending approvals (oldest first) -----
$pending = $pdo->query(
    "SELECT m.id, m.title, m.created_at, u.name AS uploader, s.name AS subject
       FROM materials m
       JOIN users u    ON u.id = m.user_id
       JOIN subjects s ON s.id = m.subject_id
      WHERE m.status = 'pending'
      ORDER BY m.created_at ASC LIMIT 5"
)->fetchAll();

// ----- Most downloaded -----
$top = $pdo->query(
    "SELECT m.title, m.downloads, s.name AS subject
       FROM materials m JOIN subjects s ON s.id = m.subject_id
      WHERE m.status = 'approved'
      ORDER BY m.downloads DESC LIMIT 5"
)->fetchAll();

$pageTitle = 'Admin dashboard';
$layout    = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Admin dashboard</h1></div>

<div class="row g-3 mb-4">
  <?php
  $stats = [
      [$totalUsers,    'Students'],
      [$totalApproved, 'Approved materials'],
      [$totalDownload, 'Downloads'],
      [$pendingCount,  'Pending approvals'],
  ];
  foreach ($stats as [$num, $label]): ?>
    <div class="col-6 col-xl-3">
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

<section class="card-sc mb-4">
  <h2 class="section-title">Approved uploads per subject</h2>
  <div class="chart-box">
    <canvas id="subjectChart" role="img" aria-label="Bar chart of approved uploads for each subject">
      Approved uploads per subject: <?= e(implode(', ', array_map(fn($l, $v) => "$l $v", $chartLabels, $chartValues))) ?>
    </canvas>
  </div>
</section>

<div class="row g-4">
  <div class="col-xl-7">
    <section class="card-sc h-100">
      <div class="d-flex justify-content-between align-items-center">
        <h2 class="section-title mb-0">Pending materials</h2>
        <a class="btn-sc btn-sc-sm" href="<?= url('admin/materials.php?filter=pending') ?>">Review all</a>
      </div>
      <?php if (!$pending): ?>
        <div class="empty-state">Nothing is waiting for approval.</div>
      <?php else: ?>
        <div class="table-wrap mt-2">
          <table class="table-sc">
            <thead><tr><th>Title</th><th>Uploaded by</th><th>Subject</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($pending as $p): ?>
              <tr>
                <td><strong><?= e($p['title']) ?></strong></td>
                <td><?= e($p['uploader']) ?></td>
                <td><?= e($p['subject']) ?></td>
                <td><?= e(date('j M Y', strtotime($p['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <div class="col-xl-5">
    <section class="card-sc h-100">
      <h2 class="section-title">Most downloaded</h2>
      <?php if (!$top): ?>
        <div class="empty-state">No approved materials yet.</div>
      <?php endif; ?>
      <?php foreach ($top as $t): ?>
        <div class="list-row">
          <div>
            <div class="list-row-title"><?= e($t['title']) ?></div>
            <div class="list-row-meta"><?= e($t['subject']) ?></div>
          </div>
          <span class="badge-sc"><?= number_format((int)$t['downloads']) ?> downloads</span>
        </div>
      <?php endforeach; ?>
    </section>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  Chart.defaults.color = '#55616E';
  new Chart(document.getElementById('subjectChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($chartLabels) ?>,
      datasets: [{
        label: 'Approved uploads',
        data: <?= json_encode($chartValues) ?>,
        backgroundColor: '#00A651',
        hoverBackgroundColor: '#0057B8',
        borderRadius: 8,
        maxBarThickness: 56
      }]
    },
    options: {
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#E6EBF0' } },
        x: { grid: { display: false } }
      }
    }
  });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
