<?php
// student/groups.php: browse study groups, join or leave.
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/groups_lib.php';

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $gid = (int)($_POST['group_id'] ?? 0);
    if (($_POST['action'] ?? '') === 'join') {
        group_join($pdo, $userId, $gid);
    } elseif (($_POST['action'] ?? '') === 'leave') {
        group_leave($pdo, $userId, $gid);
    }
    header('Location: ' . url('student/groups.php'));
    exit;
}

$q       = trim($_GET['q'] ?? '');
$subject = (int)($_GET['subject'] ?? 0);
$where   = ['1 = 1'];
$params  = [$userId];
if ($q !== '') {
    $where[]  = '(g.name LIKE ? OR g.description LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($subject > 0) {
    $where[]  = 'g.subject_id = ?';
    $params[] = $subject;
}

$stmt = $pdo->prepare(
    'SELECT g.id, g.name, g.description, g.subject_id, s.name AS subject,
            (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id) AS members,
            (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.id AND gm.user_id = ?) AS is_member,
            (SELECT MIN(ss.session_date) FROM study_sessions ss WHERE ss.group_id = g.id AND ss.session_date >= NOW()) AS next_session
       FROM study_groups g JOIN subjects s ON s.id = g.subject_id
      WHERE ' . implode(' AND ', $where) . ' ORDER BY g.created_at DESC'
);
$stmt->execute($params);
$groups   = $stmt->fetchAll();
$subjects = $pdo->query('SELECT id, name FROM subjects ORDER BY name')->fetchAll();

$pageTitle = 'Study groups';
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1>Study groups</h1>
  <a class="btn-sc" href="<?= url('student/group_form.php') ?>">Create group</a>
</div>

<form class="card-sc row g-2 mb-4" method="get" action="" role="search">
  <div class="col-lg-7"><label class="visually-hidden" for="q">Search groups</label>
    <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Search groups"></div>
  <div class="col-lg-3"><label class="visually-hidden" for="subject">Subject</label>
    <select class="form-select" id="subject" name="subject">
      <option value="0">All subjects</option>
      <?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>"<?= $subject === (int)$s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
    </select></div>
  <div class="col-lg-2 d-grid"><button class="btn-sc" type="submit">Search</button></div>
</form>

<?php if (!$groups): ?><div class="card-sc empty-state">No groups found. <a href="<?= url('student/group_form.php') ?>">Create one</a>.</div><?php endif; ?>
<div class="row g-4">
<?php foreach ($groups as $g): ?>
  <div class="col-md-6 col-lg-4">
    <article class="card-sc card-sc-flush h-100 d-flex flex-column">
      <div class="cover-tile" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($g['name'], 0, 2))) ?></div>
      <div class="p-4 d-flex flex-column gap-2 flex-grow-1">
        <div><?= subject_badge($g['subject'], (int)$g['subject_id']) ?></div>
        <h2 class="h5 mb-0"><a class="text-decoration-none" href="<?= url('student/group_view.php?id=' . (int)$g['id']) ?>"><?= e($g['name']) ?></a></h2>
        <div class="list-row-meta"><?= (int)$g['members'] ?> member<?= (int)$g['members'] === 1 ? '' : 's' ?></div>
        <div class="list-row-meta">Next session: <?= $g['next_session'] ? e(date('D j M, g:i A', strtotime($g['next_session']))) : 'none planned' ?></div>
        <div class="mt-auto pt-2 d-flex gap-2">
          <a class="btn-sc-outline" href="<?= url('student/group_view.php?id=' . (int)$g['id']) ?>">View</a>
          <form method="post" action="" class="flex-grow-1 d-grid">
            <?= csrf_field() ?><input type="hidden" name="group_id" value="<?= (int)$g['id'] ?>">
            <input type="hidden" name="action" value="<?= $g['is_member'] ? 'leave' : 'join' ?>">
            <button class="<?= $g['is_member'] ? 'btn-sc-outline' : 'btn-sc' ?>" type="submit"><?= $g['is_member'] ? 'Leave group' : 'Join group' ?></button>
          </form>
        </div>
      </div>
    </article>
  </div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
