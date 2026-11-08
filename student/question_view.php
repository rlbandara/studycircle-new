<?php
// student/question_view.php: one question, its answers, and the answer form.
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$userId  = (int)$_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$id      = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT qs.*, s.name AS subject, u.name AS asker
       FROM questions qs JOIN subjects s ON s.id = qs.subject_id JOIN users u ON u.id = qs.user_id
      WHERE qs.id = ?'
);
$stmt->execute([$id]);
$qs = $stmt->fetch();
if (!$qs) {
    set_flash('danger', 'That question was not found.');
    header('Location: ' . url('student/forum.php'));
    exit;
}
$isOwner = (int)$qs['user_id'] === $userId;
$self    = url('student/question_view.php?id=' . $id);
$errors  = [];
$answerBody = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $aid    = (int)($_POST['answer_id'] ?? 0);

    if ($action === 'answer') {
        $answerBody = trim($_POST['body'] ?? '');
        if (mb_strlen($answerBody) < 2 || mb_strlen($answerBody) > 3000) {
            $errors['body'] = 'Write an answer (2 to 3000 characters).';
        } else {
            $pdo->prepare('INSERT INTO answers (question_id, user_id, body) VALUES (?, ?, ?)')->execute([$id, $userId, $answerBody]);
            set_flash('success', 'Answer posted.');
            header('Location: ' . $self);
            exit;
        }
    } elseif ($action === 'best' && $isOwner) {
        $stmt = $pdo->prepare('SELECT is_best FROM answers WHERE id = ? AND question_id = ?');
        $stmt->execute([$aid, $id]);
        $cur = $stmt->fetch();
        if ($cur) {
            $pdo->prepare('UPDATE answers SET is_best = 0 WHERE question_id = ?')->execute([$id]);
            if (!(int)$cur['is_best']) {
                $pdo->prepare('UPDATE answers SET is_best = 1 WHERE id = ?')->execute([$aid]);
            }
        }
        header('Location: ' . $self);
        exit;
    } elseif ($action === 'delete_answer') {
        $pdo->prepare('DELETE FROM answers WHERE id = ? AND question_id = ? AND (user_id = ? OR ? = 1)')
            ->execute([$aid, $id, $userId, $isAdmin ? 1 : 0]);
        set_flash('success', 'Answer deleted.');
        header('Location: ' . $self);
        exit;
    } elseif ($action === 'delete_question' && ($isOwner || $isAdmin)) {
        $pdo->prepare('DELETE FROM questions WHERE id = ?')->execute([$id]);
        set_flash('success', 'Question deleted.');
        header('Location: ' . url('student/forum.php'));
        exit;
    }
}

$stmt = $pdo->prepare(
    'SELECT a.id, a.user_id, a.body, a.is_best, a.created_at, u.name
       FROM answers a JOIN users u ON u.id = a.user_id
      WHERE a.question_id = ? ORDER BY a.is_best DESC, a.created_at ASC'
);
$stmt->execute([$id]);
$answers = $stmt->fetchAll();

$pageTitle = $qs['title'];
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="mb-3"><a href="<?= url('student/forum.php') ?>">Back to forum</a></div>

<article class="card-sc mb-4">
  <div class="mb-2"><?= subject_badge($qs['subject'], (int)$qs['subject_id']) ?></div>
  <h1 class="h2"><?= e($qs['title']) ?></h1>
  <div class="list-row-meta mb-3">Asked by <?= e($qs['asker']) ?> on <?= e(date('j M Y, g:i A', strtotime($qs['created_at']))) ?></div>
  <p><?= nl2br(e($qs['body'])) ?></p>
  <?php if ($isOwner || $isAdmin): ?>
    <div class="d-flex gap-2">
      <?php if ($isOwner): ?><a class="btn-sc-outline btn-sc-sm" href="<?= url('student/question_form.php?id=' . $id) ?>">Edit</a><?php endif; ?>
      <form method="post" action="" data-confirm="Delete this question and all its answers?">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="delete_question">
        <button class="btn-sc-danger btn-sc-sm" type="submit">Delete</button>
      </form>
    </div>
  <?php endif; ?>
</article>

<h2 class="section-title"><?= count($answers) ?> answer<?= count($answers) === 1 ? '' : 's' ?></h2>
<?php if (!$answers): ?><div class="card-sc empty-state mb-4">No answers yet. Be the first to help.</div><?php endif; ?>
<?php foreach ($answers as $a): ?>
  <div class="card-sc mb-3 <?= $a['is_best'] ? 'answer-best' : '' ?>">
    <div class="d-flex justify-content-between gap-2 flex-wrap mb-2">
      <div class="d-flex align-items-center gap-2">
        <span class="sc-avatar" aria-hidden="true"><?= e(initials($a['name'])) ?></span>
        <div><strong><?= e($a['name']) ?></strong><div class="list-row-meta"><?= e(date('j M Y, g:i A', strtotime($a['created_at']))) ?></div></div>
      </div>
      <?= $a['is_best'] ? '<span class="badge-sc badge-sc-success align-self-start">Best answer</span>' : '' ?>
    </div>
    <p class="mb-3"><?= nl2br(e($a['body'])) ?></p>
    <div class="d-flex gap-2 flex-wrap">
      <?php if ($isOwner): ?>
        <form method="post" action="">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="answer_id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="best">
          <button class="btn-sc-outline btn-sc-sm" type="submit"><?= $a['is_best'] ? 'Remove best mark' : 'Mark as best answer' ?></button>
        </form>
      <?php endif; ?>
      <?php if ((int)$a['user_id'] === $userId || $isAdmin): ?>
        <form method="post" action="" data-confirm="Delete this answer?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="answer_id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="action" value="delete_answer">
          <button class="btn-sc-danger btn-sc-sm" type="submit">Delete</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>

<form class="card-sc d-flex flex-column gap-3 mt-4" method="post" action="" novalidate>
  <h2 class="section-title mb-0">Your answer</h2>
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="answer">
  <div>
    <label class="visually-hidden" for="body">Your answer</label>
    <textarea class="form-control" id="body" name="body" rows="4" maxlength="3000" required><?= e($answerBody) ?></textarea>
    <?= err_msg($errors, 'body') ?>
  </div>
  <div><button class="btn-sc" type="submit">Post answer</button></div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
