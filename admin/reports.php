<?php
// admin/reports.php: resource usage and engagement reports (print or download as CSV).
require_once __DIR__ . '/../includes/admin_check.php';

$one = function (string $sql) use ($pdo): int {
    return (int)$pdo->query($sql)->fetchColumn();
};

// ----- CSV download: every material with its usage numbers -----
if (($_GET['export'] ?? '') === 'resources') {
    $rows = $pdo->query(
        "SELECT m.title, s.name AS subject, u.name AS uploader, m.status, m.downloads,
                COALESCE(ROUND(AVG(r.rating), 2), 0) AS avg_rating, COUNT(r.id) AS ratings, DATE(m.created_at) AS uploaded
           FROM materials m
           JOIN subjects s ON s.id = m.subject_id
           JOIN users u    ON u.id = m.user_id
           LEFT JOIN material_ratings r ON r.material_id = m.id
          GROUP BY m.id, m.title, s.name, u.name, m.status, m.downloads, m.created_at
          ORDER BY m.downloads DESC"
    )->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="studycircle-resource-usage-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");   // lets Excel read the file as UTF-8
    fputcsv($out, ['Title', 'Subject', 'Uploaded by', 'Status', 'Downloads', 'Average rating', 'Ratings', 'Uploaded on']);
    foreach ($rows as $r) {
        fputcsv($out, array_values($r));
    }
    fclose($out);
    exit;
}

// ----- Overview numbers -----
$overview = [
    'Students'            => $one("SELECT COUNT(*) FROM users WHERE role = 'student'"),
    'Approved materials'  => $one("SELECT COUNT(*) FROM materials WHERE status = 'approved'"),
    'Total downloads'     => $one('SELECT COALESCE(SUM(downloads), 0) FROM materials'),
    'Reviews'             => $one('SELECT COUNT(*) FROM material_ratings'),
    'Questions'           => $one('SELECT COUNT(*) FROM questions'),
    'Answers'             => $one('SELECT COUNT(*) FROM answers'),
    'Study groups'        => $one('SELECT COUNT(*) FROM study_groups'),
    'Study sessions'      => $one('SELECT COUNT(*) FROM study_sessions'),
];

$bySubject = $pdo->query(
    "SELECT s.name,
            (SELECT COUNT(*) FROM materials m WHERE m.subject_id = s.id AND m.status = 'approved') AS materials,
            (SELECT COALESCE(SUM(m.downloads), 0) FROM materials m WHERE m.subject_id = s.id) AS downloads,
            (SELECT COUNT(*) FROM questions q WHERE q.subject_id = s.id) AS questions,
            (SELECT COUNT(*) FROM study_groups g WHERE g.subject_id = s.id) AS groups_count
       FROM subjects s ORDER BY downloads DESC, s.name"
)->fetchAll();

$contributors = $pdo->query(
    "SELECT u.name, COUNT(m.id) AS uploads, COALESCE(SUM(m.downloads), 0) AS downloads
       FROM users u JOIN materials m ON m.user_id = u.id AND m.status = 'approved'
      GROUP BY u.id, u.name ORDER BY uploads DESC, downloads DESC LIMIT 5"
)->fetchAll();

$topDownloaded = $pdo->query(
    "SELECT m.title, m.downloads, s.name AS subject
       FROM materials m JOIN subjects s ON s.id = m.subject_id
      WHERE m.status = 'approved' ORDER BY m.downloads DESC LIMIT 5"
)->fetchAll();

$topRated = $pdo->query(
    "SELECT m.title, ROUND(AVG(r.rating), 1) AS avg_rating, COUNT(r.id) AS ratings
       FROM materials m JOIN material_ratings r ON r.material_id = m.id
      WHERE m.status = 'approved' GROUP BY m.id, m.title
      ORDER BY avg_rating DESC, ratings DESC LIMIT 5"
)->fetchAll();

$pageTitle = 'Reports';
$layout    = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div><h1 class="mb-1">Reports</h1><div class="text-muted-sc">Generated on <?= e(date('j M Y')) ?></div></div>
  <div class="d-flex gap-2 no-print">
    <a class="btn-sc" href="<?= url('admin/reports.php?export=resources') ?>">Download CSV</a>
    <button class="btn-sc-outline" type="button" onclick="window.print()">Print</button>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ($overview as $label => $num): ?>
    <div class="col-6 col-xl-3"><div class="card-sc stat-card">
      <span class="stat-icon" aria-hidden="true"></span>
      <div><div class="stat-number"><?= number_format($num) ?></div><div class="text-muted-sc"><?= e($label) ?></div></div>
    </div></div>
  <?php endforeach; ?>
</div>

<section class="card-sc mb-4">
  <h2 class="section-title">Usage by subject</h2>
  <div class="table-wrap"><table class="table-sc">
    <thead><tr><th>Subject</th><th>Approved materials</th><th>Downloads</th><th>Questions</th><th>Study groups</th></tr></thead>
    <tbody>
    <?php foreach ($bySubject as $r): ?>
      <tr><td><strong><?= e($r['name']) ?></strong></td><td><?= (int)$r['materials'] ?></td><td><?= number_format((int)$r['downloads']) ?></td><td><?= (int)$r['questions'] ?></td><td><?= (int)$r['groups_count'] ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>

<div class="row g-4">
  <div class="col-xl-4"><section class="card-sc h-100">
    <h2 class="section-title">Top contributors</h2>
    <?php if (!$contributors): ?><div class="empty-state">No approved uploads yet.</div><?php endif; ?>
    <?php foreach ($contributors as $r): ?>
      <div class="list-row"><div><div class="list-row-title"><?= e($r['name']) ?></div><div class="list-row-meta"><?= (int)$r['downloads'] ?> downloads</div></div>
        <span class="badge-sc"><?= (int)$r['uploads'] ?> uploads</span></div>
    <?php endforeach; ?>
  </section></div>
  <div class="col-xl-4"><section class="card-sc h-100">
    <h2 class="section-title">Most downloaded</h2>
    <?php if (!$topDownloaded): ?><div class="empty-state">Nothing yet.</div><?php endif; ?>
    <?php foreach ($topDownloaded as $r): ?>
      <div class="list-row"><div><div class="list-row-title"><?= e($r['title']) ?></div><div class="list-row-meta"><?= e($r['subject']) ?></div></div>
        <span class="badge-sc"><?= (int)$r['downloads'] ?></span></div>
    <?php endforeach; ?>
  </section></div>
  <div class="col-xl-4"><section class="card-sc h-100">
    <h2 class="section-title">Top rated</h2>
    <?php if (!$topRated): ?><div class="empty-state">No ratings yet.</div><?php endif; ?>
    <?php foreach ($topRated as $r): ?>
      <div class="list-row"><div><div class="list-row-title"><?= e($r['title']) ?></div><div class="list-row-meta"><?= (int)$r['ratings'] ?> rating<?= (int)$r['ratings'] === 1 ? '' : 's' ?></div></div>
        <span class="badge-sc badge-sc-amber"><?= e($r['avg_rating']) ?> / 5</span></div>
    <?php endforeach; ?>
  </section></div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
