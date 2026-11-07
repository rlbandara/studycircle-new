<?php
// admin/materials.php: approve, reject and delete materials.
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$filter  = $_GET['filter'] ?? 'pending';
$filters = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'];
if (!isset($filters[$filter])) {
    $filter = 'pending';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'approve' || $action === 'reject') {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $pdo->prepare('UPDATE materials SET status = ? WHERE id = ?')->execute([$status, $id]);
        set_flash('success', 'Material ' . $status . '.');
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare('SELECT file_path FROM materials WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $pdo->prepare('DELETE FROM materials WHERE id = ?')->execute([$id]);
            $path = UPLOAD_DIR . basename($row['file_path']);
            if (is_file($path)) {
                unlink($path);
            }
            set_flash('success', 'Material deleted.');
        }
    }
    header('Location: ' . url('admin/materials.php?filter=' . urlencode($_POST['filter'] ?? 'pending')));
    exit;
}

$sql = 'SELECT m.id, m.title, m.status, m.downloads, m.created_at, m.subject_id, s.name AS subject, u.name AS uploader
          FROM materials m
          JOIN subjects s ON s.id = m.subject_id
          JOIN users u    ON u.id = m.user_id';
$params = [];
if ($filter !== 'all') {
    $sql .= ' WHERE m.status = ?';
    $params[] = $filter;
}
$stmt = $pdo->prepare($sql . ' ORDER BY m.created_at DESC');
$stmt->execute($params);
$materials = $stmt->fetchAll();

$pageTitle = 'Materials';
$layout    = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Materials</h1></div>

<nav class="d-flex flex-wrap gap-2 mb-4" aria-label="Filter by status">
  <?php foreach ($filters as $key => $label): ?>
    <a class="<?= $filter === $key ? 'btn-sc' : 'btn-sc-outline' ?>" href="<?= url('admin/materials.php?filter=' . $key) ?>"<?= $filter === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<section class="card-sc">
  <?php if (!$materials): ?>
    <div class="empty-state">No <?= e(strtolower($filters[$filter])) ?> materials.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table-sc">
        <thead><tr><th>Title</th><th>Uploaded by</th><th>Subject</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($materials as $m): ?>
          <tr>
            <td><a href="<?= url('student/material_view.php?id=' . (int)$m['id']) ?>"><strong><?= e($m['title']) ?></strong></a></td>
            <td><?= e($m['uploader']) ?></td>
            <td><?= subject_badge($m['subject'], (int)$m['subject_id']) ?></td>
            <td><?= status_badge($m['status']) ?></td>
            <td><?= e(date('j M Y', strtotime($m['created_at']))) ?></td>
            <td class="text-end">
              <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
                <?php foreach ([['approve', 'Approve', 'btn-sc', 'approved'], ['reject', 'Reject', 'btn-sc-outline', 'rejected']] as [$act, $label, $cls, $skip]): ?>
                  <?php if ($m['status'] !== $skip): ?>
                    <form method="post" action="">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                      <input type="hidden" name="filter" value="<?= e($filter) ?>">
                      <input type="hidden" name="action" value="<?= $act ?>">
                      <button class="<?= $cls ?> btn-sc-sm" type="submit"><?= $label ?></button>
                    </form>
                  <?php endif; ?>
                <?php endforeach; ?>
                <form method="post" action="" data-confirm="Delete this material and its file?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                  <input type="hidden" name="filter" value="<?= e($filter) ?>">
                  <input type="hidden" name="action" value="delete">
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
