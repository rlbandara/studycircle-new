<?php
// admin/groups.php: view and delete study groups.
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pdo->prepare('DELETE FROM study_groups WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
    set_flash('success', 'Group deleted.');
    header('Location: ' . url('admin/groups.php'));
    exit;
}

$groups = $pdo->query(
    'SELECT g.id, g.name, g.created_at, g.subject_id, s.name AS subject, u.name AS creator,
            (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS members,
            (SELECT COUNT(*) FROM study_sessions ss WHERE ss.group_id = g.id) AS sessions
       FROM study_groups g JOIN subjects s ON s.id = g.subject_id JOIN users u ON u.id = g.creator_id
      ORDER BY g.created_at DESC'
)->fetchAll();

$pageTitle = 'Study groups';
$layout    = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Study groups</h1></div>
<section class="card-sc"><div class="table-wrap">
  <table class="table-sc">
    <thead><tr><th>Group</th><th>Subject</th><th>Created by</th><th>Members</th><th>Sessions</th><th>Created</th><th class="text-end">Action</th></tr></thead>
    <tbody>
    <?php foreach ($groups as $g): ?>
      <tr>
        <td><a href="<?= url('student/group_view.php?id=' . (int)$g['id']) ?>"><strong><?= e($g['name']) ?></strong></a></td>
        <td><?= subject_badge($g['subject'], (int)$g['subject_id']) ?></td>
        <td><?= e($g['creator']) ?></td><td><?= (int)$g['members'] ?></td><td><?= (int)$g['sessions'] ?></td>
        <td><?= e(date('j M Y', strtotime($g['created_at']))) ?></td>
        <td class="text-end">
          <form method="post" action="" data-confirm="Delete this group, its members and sessions?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
            <button class="btn-sc-danger btn-sc-sm" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$groups): ?><tr><td colspan="7" class="empty-state">No groups yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div></section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
