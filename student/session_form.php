<?php
// student/session_form.php: schedule a study session (new: ?group=ID) or edit one (?id=ID).
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$userId  = (int)$_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$sid     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$gid     = (int)($_GET['group'] ?? $_POST['group'] ?? 0);
$session = null;

if ($sid > 0) {
    $stmt = $pdo->prepare('SELECT ss.*, g.creator_id FROM study_sessions ss JOIN study_groups g ON g.id = ss.group_id WHERE ss.id = ?');
    $stmt->execute([$sid]);
    $session = $stmt->fetch();
    if (!$session || ((int)$session['created_by'] !== $userId && (int)$session['creator_id'] !== $userId && !$isAdmin)) {
        set_flash('danger', 'That session was not found.');
        header('Location: ' . url('student/groups.php'));
        exit;
    }
    $gid = (int)$session['group_id'];
} else {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id = ?');
    $stmt->execute([$gid, $userId]);
    if ($gid < 1 || !$stmt->fetchColumn()) {
        set_flash('danger', 'Join the group first to schedule a session.');
        header('Location: ' . url($gid ? 'student/group_view.php?id=' . $gid : 'student/groups.php'));
        exit;
    }
}

$stmt = $pdo->prepare('SELECT name FROM study_groups WHERE id = ?');
$stmt->execute([$gid]);
$groupName = (string)$stmt->fetchColumn();

$form = [
    'title'    => $session['title'] ?? '',
    'date'     => $session ? date('Y-m-d\TH:i', strtotime($session['session_date'])) : '',
    'location' => $session['location'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form = ['title' => trim($_POST['title'] ?? ''), 'date' => trim($_POST['date'] ?? ''), 'location' => trim($_POST['location'] ?? '')];

    if (mb_strlen($form['title']) < 3 || mb_strlen($form['title']) > 150) {
        $errors['title'] = 'Enter a title (3 to 150 characters).';
    }
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $form['date']);
    if (!$dt) {
        $errors['date'] = 'Choose a date and time.';
    } elseif (!$session && $dt <= new DateTime()) {
        $errors['date'] = 'Choose a date and time in the future.';
    }
    if (mb_strlen($form['location']) > 200) {
        $errors['location'] = 'Keep the location under 200 characters.';
    }

    if (!$errors) {
        $when = $dt->format('Y-m-d H:i:s');
        if ($session) {
            $pdo->prepare('UPDATE study_sessions SET title = ?, session_date = ?, location = ? WHERE id = ?')
                ->execute([$form['title'], $when, $form['location'] ?: null, $sid]);
            set_flash('success', 'Session updated.');
        } else {
            $pdo->prepare('INSERT INTO study_sessions (group_id, title, session_date, location, created_by) VALUES (?, ?, ?, ?, ?)')
                ->execute([$gid, $form['title'], $when, $form['location'] ?: null, $userId]);
            set_flash('success', 'Session scheduled.');
        }
        header('Location: ' . url('student/group_view.php?id=' . $gid));
        exit;
    }
}

$pageTitle = $session ? 'Edit session' : 'Schedule session';
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1><?= e($pageTitle) ?></h1></div>
<form class="card-sc d-flex flex-column gap-3" style="max-width:720px" method="post" action="" novalidate>
  <div class="text-muted-sc">Group: <strong><?= e($groupName) ?></strong></div>
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $sid ?>"><input type="hidden" name="group" value="<?= $gid ?>">
  <div><label class="form-label" for="title">Title</label>
    <input class="form-control" id="title" name="title" maxlength="150" value="<?= e($form['title']) ?>" required><?= err_msg($errors, 'title') ?></div>
  <div><label class="form-label" for="date">Date and time</label>
    <input class="form-control" type="datetime-local" id="date" name="date" value="<?= e($form['date']) ?>" required><?= err_msg($errors, 'date') ?></div>
  <div><label class="form-label" for="location">Location or online link (optional)</label>
    <input class="form-control" id="location" name="location" maxlength="200" value="<?= e($form['location']) ?>"><?= err_msg($errors, 'location') ?></div>
  <div class="d-flex gap-2">
    <button class="btn-sc" type="submit"><?= $session ? 'Save changes' : 'Schedule' ?></button>
    <a class="btn-sc-outline" href="<?= url('student/group_view.php?id=' . $gid) ?>">Cancel</a>
  </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
