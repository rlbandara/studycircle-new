<?php
// student/forum.php: list of questions with search and subject filter.
require_once __DIR__ . '/../includes/auth_check.php';

$q       = trim($_GET['q'] ?? '');
$subject = (int)($_GET['subject'] ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$where  = ['1 = 1'];
$params = [];
if ($q !== '') {
    $where[]  = '(qs.title LIKE ? OR qs.body LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($subject > 0) {
    $where[]  = 'qs.subject_id = ?';
    $params[] = $subject;
}
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM questions qs WHERE $whereSql");
$stmt->execute($params);
$totalPages = max(1, (int)ceil((int)$stmt->fetchColumn() / $perPage));
$page   = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT qs.id, qs.title, qs.created_at, qs.subject_id, s.name AS subject, u.name AS asker,
            (SELECT COUNT(*) FROM answers a WHERE a.question_id = qs.id) AS answer_count,
            (SELECT COUNT(*) FROM answers a WHERE a.question_id = qs.id AND a.is_best = 1) AS has_best
       FROM questions qs
       JOIN subjects s ON s.id = qs.subject_id
       JOIN users u    ON u.id = qs.user_id
      WHERE $whereSql
      ORDER BY qs.created_at DESC
      LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$questions = $stmt->fetchAll();
$subjects  = $pdo->query('SELECT id, name FROM subjects ORDER BY name')->fetchAll();

$pageTitle = 'Forum';
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1>Discussion forum</h1>
  <a class="btn-sc" href="<?= url('student/question_form.php') ?>">Ask question</a>
</div>

<form class="card-sc row g-2 mb-4" method="get" action="" role="search">
  <div class="col-lg-7">
    <label class="visually-hidden" for="q">Search questions</label>
    <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Search questions">
  </div>
  <div class="col-lg-3">
    <label class="visually-hidden" for="subject">Subject</label>
    <select class="form-select" id="subject" name="subject">
      <option value="0">All subjects</option>
      <?php foreach ($subjects as $s): ?>
        <option value="<?= (int)$s['id'] ?>"<?= $subject === (int)$s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-lg-2 d-grid"><button class="btn-sc" type="submit">Search</button></div>
</form>

<?php if (!$questions): ?>
  <div class="card-sc empty-state">No questions found. <a href="<?= url('student/question_form.php') ?>">Ask the first one</a>.</div>
<?php endif; ?>
<?php foreach ($questions as $qs): ?>
  <div class="card-sc d-flex gap-3 align-items-center mb-3">
    <div class="answer-count"><strong><?= (int)$qs['answer_count'] ?></strong><span class="list-row-meta">answers</span></div>
    <div class="flex-grow-1">
      <h2 class="h5 mb-1"><a class="text-decoration-none" href="<?= url('student/question_view.php?id=' . (int)$qs['id']) ?>"><?= e($qs['title']) ?></a></h2>
      <div class="list-row-meta d-flex flex-wrap gap-2 align-items-center">
        <?= subject_badge($qs['subject'], (int)$qs['subject_id']) ?>
        <span>Asked by <?= e($qs['asker']) ?> on <?= e(date('j M Y', strtotime($qs['created_at']))) ?></span>
      </div>
    </div>
    <?= $qs['has_best'] ? '<span class="badge-sc badge-sc-success">Answered</span>' : '<span class="badge-sc badge-sc-amber">Open</span>' ?>
  </div>
<?php endforeach; ?>
<?= pager($page, $totalPages) ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
