<?php
// student/material_view.php: one material with download, ratings and comments.
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$userId  = (int)$_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$id      = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT m.*, s.name AS subject, u.name AS uploader
       FROM materials m
       JOIN subjects s ON s.id = m.subject_id
       JOIN users u    ON u.id = m.user_id
      WHERE m.id = ?'
);
$stmt->execute([$id]);
$m = $stmt->fetch();

// Only approved materials are public. Owners and admins can also see their own / pending ones.
if (!$m || ($m['status'] !== 'approved' && (int)$m['user_id'] !== $userId && !$isAdmin)) {
    set_flash('danger', 'That material was not found.');
    header('Location: ' . url('student/materials.php'));
    exit;
}
$isOwner  = (int)$m['user_id'] === $userId;
$canRate  = $m['status'] === 'approved' && !$isOwner;
$errors   = [];

$stmt = $pdo->prepare('SELECT rating, comment FROM material_ratings WHERE material_id = ? AND user_id = ?');
$stmt->execute([$id, $userId]);
$mine = $stmt->fetch() ?: ['rating' => 0, 'comment' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if (!$canRate) {
        set_flash('danger', 'You cannot rate this material.');
        header('Location: ' . url('student/material_view.php?id=' . $id));
        exit;
    }
    if ($rating < 1 || $rating > 5) {
        $errors['rating'] = 'Choose a rating from 1 to 5.';
    }
    if (mb_strlen($comment) > 500) {
        $errors['comment'] = 'Keep your comment under 500 characters.';
    }
    $mine = ['rating' => $rating, 'comment' => $comment];

    if (!$errors) {
        // One rating per student: a second submit updates the first
        $pdo->prepare(
            'INSERT INTO material_ratings (material_id, user_id, rating, comment) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment)'
        )->execute([$id, $userId, $rating, $comment !== '' ? $comment : null]);
        set_flash('success', 'Thanks for your review.');
        header('Location: ' . url('student/material_view.php?id=' . $id));
        exit;
    }
}

$stmt = $pdo->prepare('SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS cnt FROM material_ratings WHERE material_id = ?');
$stmt->execute([$id]);
$summary = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT r.rating, r.comment, r.created_at, u.name
       FROM material_ratings r JOIN users u ON u.id = r.user_id
      WHERE r.material_id = ? ORDER BY r.created_at DESC'
);
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();

$pageTitle = $m['title'];
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="mb-3"><a href="<?= url('student/materials.php') ?>">Back to materials</a></div>

<div class="row g-4">
  <div class="col-lg-8">
    <article class="card-sc">
      <div class="d-flex flex-wrap gap-2 mb-2">
        <?= subject_badge($m['subject'], (int)$m['subject_id']) ?>
        <?= $m['status'] !== 'approved' ? status_badge($m['status']) : '' ?>
      </div>
      <h1 class="h2"><?= e($m['title']) ?></h1>
      <div class="list-row-meta mb-3">
        Uploaded by <?= e($m['uploader']) ?> on <?= e(date('j M Y', strtotime($m['created_at']))) ?>, <?= (int)$m['downloads'] ?> downloads
      </div>
      <p><?= $m['description'] ? nl2br(e($m['description'])) : '<span class="text-muted-sc">No description.</span>' ?></p>
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <a class="btn-sc" href="<?= url('student/download.php?id=' . (int)$m['id']) ?>">Download <?= e($m['file_name']) ?></a>
        <?php if ($isOwner): ?>
          <a class="btn-sc-outline" href="<?= url('student/material_form.php?id=' . (int)$m['id']) ?>">Edit</a>
        <?php endif; ?>
      </div>
    </article>

    <section class="card-sc mt-4">
      <h2 class="section-title">Reviews</h2>
      <?php if (!$reviews): ?><div class="empty-state">No reviews yet. Be the first to rate this material.</div><?php endif; ?>
      <?php foreach ($reviews as $r): ?>
        <div class="list-row align-items-start">
          <div class="d-flex gap-3">
            <span class="sc-avatar" aria-hidden="true"><?= e(initials($r['name'])) ?></span>
            <div>
              <div class="list-row-title"><?= e($r['name']) ?></div>
              <div class="stars" aria-label="<?= (int)$r['rating'] ?> out of 5"><?= stars((float)$r['rating']) ?></div>
              <?php if ($r['comment']): ?><div><?= e($r['comment']) ?></div><?php endif; ?>
            </div>
          </div>
          <div class="list-row-meta"><?= e(date('j M Y', strtotime($r['created_at']))) ?></div>
        </div>
      <?php endforeach; ?>
    </section>
  </div>

  <div class="col-lg-4">
    <section class="card-sc text-center">
      <div class="stat-number"><?= $summary['cnt'] ? number_format((float)$summary['avg_rating'], 1) : '-' ?></div>
      <div class="stars" aria-hidden="true"><?= stars((float)$summary['avg_rating']) ?></div>
      <div class="text-muted-sc"><?= (int)$summary['cnt'] ?> review<?= (int)$summary['cnt'] === 1 ? '' : 's' ?></div>
    </section>

    <?php if ($canRate): ?>
      <form class="card-sc mt-4 d-flex flex-column gap-3" method="post" action="" novalidate>
        <h2 class="section-title mb-0"><?= $mine['rating'] ? 'Update your review' : 'Rate this material' ?></h2>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <div>
          <label class="form-label" for="rating">Rating</label>
          <select class="form-select" id="rating" name="rating" required>
            <option value="0">Choose</option>
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <option value="<?= $i ?>"<?= (int)$mine['rating'] === $i ? ' selected' : '' ?>><?= $i ?> out of 5</option>
            <?php endfor; ?>
          </select>
          <?php if (isset($errors['rating'])): ?><div class="form-text-error"><?= e($errors['rating']) ?></div><?php endif; ?>
        </div>
        <div>
          <label class="form-label" for="comment">Comment (optional)</label>
          <textarea class="form-control" id="comment" name="comment" rows="3" maxlength="500"><?= e($mine['comment']) ?></textarea>
          <?php if (isset($errors['comment'])): ?><div class="form-text-error"><?= e($errors['comment']) ?></div><?php endif; ?>
        </div>
        <button class="btn-sc" type="submit">Save review</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
