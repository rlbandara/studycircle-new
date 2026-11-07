<?php
// student/my_materials.php: my uploads with their approval status, edit and delete.
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id   = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT file_path FROM materials WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    $row = $stmt->fetch();

    if ($row) {
        $pdo->prepare('DELETE FROM materials WHERE id = ? AND user_id = ?')->execute([$id, $userId]);
        $path = UPLOAD_DIR . basename($row['file_path']);
        if (is_file($path)) {
            unlink($path);
        }
        set_flash('success', 'Material deleted.');
    } else {
        set_flash('danger', 'That material was not found.');
    }
    header('Location: ' . url('student/my_materials.php'));
    exit;
}

$stmt = $pdo->prepare(
    'SELECT m.id, m.title, m.status, m.downloads, m.created_at, m.subject_id, s.name AS subject
       FROM materials m JOIN subjects s ON s.id = m.subject_id
      WHERE m.user_id = ? ORDER BY m.created_at DESC'
);
$stmt->execute([$userId]);
$materials = $stmt->fetchAll();

$pageTitle = 'My uploads';
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1>My uploads</h1>
  <a class="btn-sc" href="<?= url('student/material_form.php') ?>">Upload material</a>
</div>

<section class="card-sc">
  <?php if (!$materials): ?>
    <div class="empty-state">You have not uploaded anything yet. Share your first note with the batch.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table-sc">
        <thead><tr><th>Title</th><th>Subject</th><th>Status</th><th>Downloads</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($materials as $m): ?>
          <tr>
            <td><strong><?= e($m['title']) ?></strong></td>
            <td><?= subject_badge($m['subject'], (int)$m['subject_id']) ?></td>
            <td><?= status_badge($m['status']) ?></td>
            <td><?= (int)$m['downloads'] ?></td>
            <td><?= e(date('j M Y', strtotime($m['created_at']))) ?></td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <a class="btn-sc-outline btn-sc-sm" href="<?= url('student/material_form.php?id=' . (int)$m['id']) ?>">Edit</a>
                <form method="post" action="" data-confirm="Delete this material? This cannot be undone.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                  <button class="btn-sc-danger btn-sc-sm" type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
