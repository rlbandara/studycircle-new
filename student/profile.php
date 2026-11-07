<?php
// student/profile.php: edit my details, change password, and see my activity.
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$userId = (int)$_SESSION['user_id'];
$errors = [];

$stmt = $pdo->prepare('SELECT name, email, password, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
$nameValue = $user['name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $nameValue = trim($_POST['name'] ?? '');
        if (mb_strlen($nameValue) < 2 || mb_strlen($nameValue) > 100) {
            $errors['name'] = 'Enter your full name (2 to 100 characters).';
        } else {
            $pdo->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([$nameValue, $userId]);
            $_SESSION['name'] = $nameValue;
            set_flash('success', 'Profile updated.');
            header('Location: ' . url('student/profile.php'));
            exit;
        }
    } elseif ($action === 'password') {
        $current = $_POST['current'] ?? '';
        $new     = $_POST['new'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if (!password_verify($current, $user['password'])) {
            $errors['current'] = 'Your current password is not correct.';
        }
        if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            $errors['new'] = 'Use at least 8 characters, with a letter and a number.';
        }
        if ($new !== $confirm) {
            $errors['confirm'] = 'The passwords do not match.';
        }
        if (!$errors) {
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            session_regenerate_id(true);
            set_flash('success', 'Password changed.');
            header('Location: ' . url('student/profile.php'));
            exit;
        }
    }
}

$stmt = $pdo->prepare('SELECT id, title, created_at FROM questions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$userId]);
$myQuestions = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT a.question_id, a.created_at, qs.title FROM answers a JOIN questions qs ON qs.id = a.question_id WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 5');
$stmt->execute([$userId]);
$myAnswers = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT g.id, g.name FROM group_members gm JOIN study_groups g ON g.id = gm.group_id WHERE gm.user_id = ? ORDER BY g.name');
$stmt->execute([$userId]);
$myGroups = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM materials WHERE user_id = ?');
$stmt->execute([$userId]);
$uploadCount = (int)$stmt->fetchColumn();

$pageTitle = 'My profile';
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>My profile</h1></div>
<div class="row g-4">
  <div class="col-lg-5 d-flex flex-column gap-4">
    <form class="card-sc d-flex flex-column gap-3" method="post" action="" novalidate>
      <h2 class="section-title mb-0">My details</h2>
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <div><label class="form-label" for="name">Full name</label>
        <input class="form-control" id="name" name="name" maxlength="100" value="<?= e($nameValue) ?>" required><?= err_msg($errors, 'name') ?></div>
      <div><label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" value="<?= e($user['email']) ?>" disabled></div>
      <div class="list-row-meta">Member since <?= e(date('j M Y', strtotime($user['created_at']))) ?></div>
      <div><button class="btn-sc" type="submit">Save details</button></div>
    </form>

    <form class="card-sc d-flex flex-column gap-3" method="post" action="" novalidate>
      <h2 class="section-title mb-0">Change password</h2>
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <div><label class="form-label" for="current">Current password</label>
        <input class="form-control" type="password" id="current" name="current" autocomplete="current-password" required><?= err_msg($errors, 'current') ?></div>
      <div><label class="form-label" for="new">New password</label>
        <input class="form-control" type="password" id="new" name="new" autocomplete="new-password" minlength="8" required><?= err_msg($errors, 'new') ?></div>
      <div><label class="form-label" for="confirm">Confirm new password</label>
        <input class="form-control" type="password" id="confirm" name="confirm" autocomplete="new-password" required><?= err_msg($errors, 'confirm') ?></div>
      <div><button class="btn-sc" type="submit">Change password</button></div>
    </form>
  </div>

  <div class="col-lg-7 d-flex flex-column gap-4">
    <section class="card-sc">
      <h2 class="section-title">My activity</h2>
      <div class="list-row"><span>Materials uploaded</span><a href="<?= url('student/my_materials.php') ?>"><?= $uploadCount ?> (view)</a></div>
      <div class="list-row"><span>Study groups joined</span><strong><?= count($myGroups) ?></strong></div>
      <?php foreach ($myGroups as $g): ?>
        <div class="list-row-meta"><a href="<?= url('student/group_view.php?id=' . (int)$g['id']) ?>"><?= e($g['name']) ?></a></div>
      <?php endforeach; ?>
    </section>
    <section class="card-sc">
      <h2 class="section-title">My recent questions</h2>
      <?php if (!$myQuestions): ?><div class="empty-state">You have not asked any questions yet.</div><?php endif; ?>
      <?php foreach ($myQuestions as $r): ?>
        <div class="list-row"><a href="<?= url('student/question_view.php?id=' . (int)$r['id']) ?>"><?= e($r['title']) ?></a>
          <span class="list-row-meta"><?= e(date('j M Y', strtotime($r['created_at']))) ?></span></div>
      <?php endforeach; ?>
    </section>
    <section class="card-sc">
      <h2 class="section-title">My recent answers</h2>
      <?php if (!$myAnswers): ?><div class="empty-state">You have not answered any questions yet.</div><?php endif; ?>
      <?php foreach ($myAnswers as $r): ?>
        <div class="list-row"><a href="<?= url('student/question_view.php?id=' . (int)$r['question_id']) ?>"><?= e($r['title']) ?></a>
          <span class="list-row-meta"><?= e(date('j M Y', strtotime($r['created_at']))) ?></span></div>
      <?php endforeach; ?>
    </section>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
