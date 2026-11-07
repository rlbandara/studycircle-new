<?php
// student/materials.php: browse approved study materials (search, subject filter, sort, pages).
require_once __DIR__ . '/../includes/auth_check.php';

$q       = trim($_GET['q'] ?? '');
$subject = (int)($_GET['subject'] ?? 0);
$sort    = $_GET['sort'] ?? 'new';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;

$orders = [
    'new'       => 'm.created_at DESC',
    'top'       => 'avg_rating DESC, rating_count DESC',
    'downloads' => 'm.downloads DESC',
];
if (!isset($orders[$sort])) {
    $sort = 'new';
}

$where  = ["m.status = 'approved'"];
$params = [];
if ($q !== '') {
    $where[]  = '(m.title LIKE ? OR m.description LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($subject > 0) {
    $where[]  = 'm.subject_id = ?';
    $params[] = $subject;
}
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM materials m WHERE $whereSql");
$stmt->execute($params);
$total      = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT m.id, m.title, m.downloads, m.subject_id, s.name AS subject, u.name AS uploader,
            COALESCE(r.avg_rating, 0) AS avg_rating, COALESCE(r.cnt, 0) AS rating_count
       FROM materials m
       JOIN subjects s ON s.id = m.subject_id
       JOIN users u    ON u.id = m.user_id
       LEFT JOIN (SELECT material_id, AVG(rating) AS avg_rating, COUNT(*) AS cnt
                    FROM material_ratings GROUP BY material_id) r ON r.material_id = m.id
      WHERE $whereSql
      ORDER BY {$orders[$sort]}
      LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$materials = $stmt->fetchAll();

$subjects = $pdo->query('SELECT id, name FROM subjects ORDER BY name')->fetchAll();

$pageTitle = 'Materials';
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1>Study materials</h1>
  <a class="btn-sc-outline" href="<?= url('student/material_form.php') ?>">Upload material</a>
</div>

<form class="card-sc row g-2 mb-4" method="get" action="" role="search">
  <div class="col-lg-5">
    <label class="visually-hidden" for="q">Search notes</label>
    <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Search notes">
  </div>
  <div class="col-6 col-lg-3">
    <label class="visually-hidden" for="subject">Subject</label>
    <select class="form-select" id="subject" name="subject">
      <option value="0">All subjects</option>
      <?php foreach ($subjects as $s): ?>
        <option value="<?= (int)$s['id'] ?>"<?= $subject === (int)$s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-lg-2">
    <label class="visually-hidden" for="sort">Sort by</label>
    <select class="form-select" id="sort" name="sort">
      <option value="new"<?= $sort === 'new' ? ' selected' : '' ?>>Newest</option>
      <option value="top"<?= $sort === 'top' ? ' selected' : '' ?>>Top rated</option>
      <option value="downloads"<?= $sort === 'downloads' ? ' selected' : '' ?>>Most downloaded</option>
    </select>
  </div>
  <div class="col-lg-2 d-grid"><button class="btn-sc" type="submit">Search</button></div>
</form>

<?php if (!$materials): ?>
  <div class="card-sc empty-state">No materials match your search. Try another word or subject, or <a href="<?= url('student/material_form.php') ?>">upload one</a>.</div>
<?php else: ?>
  <div class="row g-4">
    <?php foreach ($materials as $m): ?>
      <div class="col-md-6 col-lg-4">
        <article class="card-sc card-sc-flush h-100 d-flex flex-column">
          <div class="card-strip"></div>
          <div class="p-4 d-flex flex-column gap-2 flex-grow-1">
            <div><?= subject_badge($m['subject'], (int)$m['subject_id']) ?></div>
            <h2 class="h5 mb-0"><a class="text-decoration-none" href="<?= url('student/material_view.php?id=' . (int)$m['id']) ?>"><?= e($m['title']) ?></a></h2>
            <div class="list-row-meta">By <?= e($m['uploader']) ?></div>
            <div class="list-row-meta">
              <span class="stars" aria-hidden="true"><?= stars((float)$m['avg_rating']) ?></span>
              <?= $m['rating_count'] ? number_format((float)$m['avg_rating'], 1) . ' (' . (int)$m['rating_count'] . ')' : 'No ratings yet' ?>
              , <?= (int)$m['downloads'] ?> downloads
            </div>
            <div class="mt-auto pt-2 d-grid">
              <a class="btn-sc" href="<?= url('student/download.php?id=' . (int)$m['id']) ?>">Download</a>
            </div>
          </div>
        </article>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="d-flex justify-content-center gap-2 mt-4 flex-wrap" aria-label="Pages">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <a class="<?= $p === $page ? 'btn-sc' : 'btn-sc-outline' ?>" style="min-width:44px;padding:0"
           href="?<?= e(http_build_query(array_merge($_GET, ['page' => $p]))) ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= $p ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
