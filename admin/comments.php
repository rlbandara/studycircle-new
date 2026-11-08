<?php
// admin/comments.php: moderate material reviews (remove a comment or the whole review).
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'clear') {
        $pdo->prepare('UPDATE material_ratings SET comment = NULL WHERE id = ?')->execute([$id]);
        set_flash('success', 'Comment removed.');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare('DELETE FROM material_ratings WHERE id = ?')->execute([$id]);
        set_flash('success', 'Review deleted.');
    }
    header('Location: ' . url('admin/comments.php'));
    exit;
}

$q      = trim($_GET['q'] ?? '');
$where  = ['1 = 1'];
$params = [];
if ($q !== '') {
    $where[]  = '(r.comment LIKE ? OR m.title LIKE ? OR u.name LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
$stmt = $pdo->prepare(
    'SELECT r.id, r.rating, r.comment, r.created_at, m.id AS material_id, m.title, u.name
       FROM material_ratings r JOIN materials m ON m.id = r.material_id JOIN users u ON u.id = r.user_id
      WHERE ' . implode(' AND ', $where) . ' ORDER BY r.created_at DESC LIMIT 100'
);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$pageTitle = 'Moderate comments';
$layout    = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Moderate comments</h1></div>
<form class="card-sc d-flex gap-2 mb-3" method="get" action="" role="search">
  <label class="visually-hidden" for="q">Search reviews</label>
  <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Search comment, material or student">
  <button class="btn-sc" type="submit">Search</button>
</form>
<section class="card-sc"><div class="table-wrap">
  <table class="table-sc">
    <thead><tr><th>Material</th><th>Student</th><th>Rating</th><th>Comment</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($reviews as $r): ?>
      <tr>
        <td><a href="<?= url('student/material_view.php?id=' . (int)$r['material_id']) ?>"><?= e($r['title']) ?></a></td>
        <td><?= e($r['name']) ?></td>
        <td><span class="stars" aria-label="<?= (int)$r['rating'] ?> out of 5"><?= stars((float)$r['rating']) ?></span></td>
        <td style="max-width:300px"><?= $r['comment'] ? e($r['comment']) : '<span class="text-muted-sc">No comment</span>' ?></td>
        <td><?= e(date('j M Y', strtotime($r['created_at']))) ?></td>
        <td class="text-end"><div class="d-inline-flex flex-wrap gap-2 justify-content-end">
          <?php if ($r['comment']): ?>
            <form method="post" action="" data-confirm="Remove this comment but keep the rating?">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="clear">
              <button class="btn-sc-outline btn-sc-sm" type="submit">Remove comment</button></form>
          <?php endif; ?>
          <form method="post" action="" data-confirm="Delete this whole review?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="delete">
            <button class="btn-sc-danger btn-sc-sm" type="submit">Delete review</button></form>
        </div></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$reviews): ?><tr><td colspan="6" class="empty-state">No reviews found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div></section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
