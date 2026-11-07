<?php
// student/group_view.php: group details, members, sessions, join / leave.
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/groups_lib.php';

$userId  = (int)$_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$id      = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT g.*, s.name AS subject, u.name AS creator
       FROM study_groups g JOIN subjects s ON s.id = g.subject_id JOIN users u ON u.id = g.creator_id
      WHERE g.id = ?'
);
$stmt->execute([$id]);
$g = $stmt->fetch();
if (!$g) {
    set_flash('danger', 'That group was not found.');
    header('Location: ' . url('student/groups.php'));
    exit;
}
$isCreator = (int)$g['creator_id'] === $userId;
$self      = url('student/group_view.php?id=' . $id);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id = ?');
$stmt->execute([$id, $userId]);
$isMember = (bool)$stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'join') {
        group_join($pdo, $userId, $id);
    } elseif ($action === 'leave') {
        group_leave($pdo, $userId, $id);
    } elseif ($action === 'delete_group' && ($isCreator || $isAdmin)) {
        $pdo->prepare('DELETE FROM study_groups WHERE id = ?')->execute([$id]);
        set_flash('success', 'Group deleted.');
        header('Location: ' . url('student/groups.php'));
        exit;
    } elseif ($action === 'delete_session') {
        $pdo->prepare('DELETE FROM study_sessions WHERE id = ? AND group_id = ? AND (created_by = ? OR ? = 1)')
            ->execute([(int)($_POST['session_id'] ?? 0), $id, $userId, ($isCreator || $isAdmin) ? 1 : 0]);
        set_flash('success', 'Session deleted.');
    }
    header('Location: ' . $self);
    exit;
}

$stmt = $pdo->prepare('SELECT u.name, gm.joined_at FROM group_members gm JOIN users u ON u.id = gm.user_id WHERE gm.group_id = ? ORDER BY gm.joined_at');
$stmt->execute([$id]);
$members = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM study_sessions WHERE group_id = ? AND session_date >= NOW() ORDER BY session_date ASC');
$stmt->execute([$id]);
$upcoming = $stmt->fetchAll();
$stmt = $pdo->prepare('SELECT * FROM study_sessions WHERE group_id = ? AND session_date < NOW() ORDER BY session_date DESC LIMIT 5');
$stmt->execute([$id]);
$past = $stmt->fetchAll();

function session_rows(array $rows, int $groupId, int $userId, bool $canManageAll): void
{
    foreach ($rows as $s): ?>
      <div class="list-row">
        <div>
          <div class="list-row-title"><?= e($s['title']) ?></div>
          <div class="list-row-meta"><?= e(date('D j M Y, g:i A', strtotime($s['session_date']))) ?><?= $s['location'] ? ', ' . e($s['location']) : '' ?></div>
        </div>
        <?php if ((int)$s['created_by'] === $userId || $canManageAll): ?>
          <div class="d-flex gap-2">
            <a class="btn-sc-outline btn-sc-sm" href="<?= url('student/session_form.php?id=' . (int)$s['id']) ?>">Edit</a>
            <form method="post" action="" data-confirm="Delete this session?">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $groupId ?>"><input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="action" value="delete_session">
              <button class="btn-sc-danger btn-sc-sm" type="submit">Delete</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach;
}

$pageTitle = $g['name'];
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="mb-3"><a href="<?= url('student/groups.php') ?>">Back to groups</a></div>
<div class="row g-4">
  <div class="col-lg-8">
    <article class="card-sc">
      <div class="mb-2"><?= subject_badge($g['subject'], (int)$g['subject_id']) ?></div>
      <h1 class="h2"><?= e($g['name']) ?></h1>
      <div class="list-row-meta mb-3">Created by <?= e($g['creator']) ?>, <?= count($members) ?> member<?= count($members) === 1 ? '' : 's' ?></div>
      <p><?= $g['description'] ? nl2br(e($g['description'])) : '<span class="text-muted-sc">No description.</span>' ?></p>
      <div class="d-flex flex-wrap gap-2">
        <form method="post" action="">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="<?= $isMember ? 'leave' : 'join' ?>">
          <button class="<?= $isMember ? 'btn-sc-outline' : 'btn-sc' ?>" type="submit"><?= $isMember ? 'Leave group' : 'Join group' ?></button>
        </form>
        <?php if ($isCreator): ?><a class="btn-sc-outline" href="<?= url('student/group_form.php?id=' . $id) ?>">Edit group</a><?php endif; ?>
        <?php if ($isCreator || $isAdmin): ?>
          <form method="post" action="" data-confirm="Delete this group, its members and sessions?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="delete_group">
            <button class="btn-sc-danger" type="submit">Delete group</button>
          </form>
        <?php endif; ?>
      </div>
    </article>

    <section class="card-sc mt-4">
      <div class="d-flex justify-content-between align-items-center">
        <h2 class="section-title mb-0">Upcoming sessions</h2>
        <?php if ($isMember): ?><a class="btn-sc btn-sc-sm" href="<?= url('student/session_form.php?group=' . $id) ?>">Schedule session</a><?php endif; ?>
      </div>
      <?php if (!$upcoming): ?><div class="empty-state">No sessions planned yet.</div><?php endif; ?>
      <?php session_rows($upcoming, $id, $userId, $isCreator || $isAdmin); ?>
      <?php if ($past): ?>
        <h3 class="h6 mt-4 mb-0 text-muted-sc">Past sessions</h3>
        <?php session_rows($past, $id, $userId, $isCreator || $isAdmin); ?>
      <?php endif; ?>
    </section>
  </div>

  <div class="col-lg-4">
    <section class="card-sc">
      <h2 class="section-title">Members</h2>
      <?php foreach ($members as $m): ?>
        <div class="d-flex align-items-center gap-2 py-2">
          <span class="sc-avatar" aria-hidden="true"><?= e(initials($m['name'])) ?></span><span><?= e($m['name']) ?></span>
        </div>
      <?php endforeach; ?>
    </section>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
