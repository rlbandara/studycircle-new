<?php
// admin/forum.php: moderate questions and answers.
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$view = ($_GET['view'] ?? 'questions') === 'answers' ? 'answers' : 'questions';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete_question') {
        $pdo->prepare('DELETE FROM questions WHERE id = ?')->execute([$id]);
        set_flash('success', 'Question deleted.');
    } elseif (($_POST['action'] ?? '') === 'delete_answer') {
        $pdo->prepare('DELETE FROM answers WHERE id = ?')->execute([$id]);
        set_flash('success', 'Answer deleted.');
    }
    header('Location: ' . url('admin/forum.php?view=' . $view));
    exit;
}

$questions = $answers = [];
if ($view === 'questions') {
    $questions = $pdo->query(
        'SELECT qs.id, qs.title, qs.created_at, qs.subject_id, s.name AS subject, u.name AS asker,
                (SELECT COUNT(*) FROM answers a WHERE a.question_id = qs.id) AS answer_count
           FROM questions qs JOIN subjects s ON s.id = qs.subject_id JOIN users u ON u.id = qs.user_id
          ORDER BY qs.created_at DESC LIMIT 100'
    )->fetchAll();
} else {
    $answers = $pdo->query(
        'SELECT a.id, a.body, a.created_at, a.question_id, qs.title, u.name
           FROM answers a JOIN questions qs ON qs.id = a.question_id JOIN users u ON u.id = a.user_id
          ORDER BY a.created_at DESC LIMIT 100'
    )->fetchAll();
}

$pageTitle = 'Moderate forum';
$layout    = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Moderate forum</h1></div>
<nav class="d-flex gap-2 mb-4" aria-label="Forum view">
  <a class="<?= $view === 'questions' ? 'btn-sc' : 'btn-sc-outline' ?>" href="<?= url('admin/forum.php?view=questions') ?>">Questions</a>
  <a class="<?= $view === 'answers' ? 'btn-sc' : 'btn-sc-outline' ?>" href="<?= url('admin/forum.php?view=answers') ?>">Answers</a>
</nav>

<section class="card-sc"><div class="table-wrap">
<?php if ($view === 'questions'): ?>
  <table class="table-sc">
    <thead><tr><th>Question</th><th>Subject</th><th>Asked by</th><th>Answers</th><th>Date</th><th class="text-end">Action</th></tr></thead>
    <tbody>
    <?php foreach ($questions as $r): ?>
      <tr>
        <td><a href="<?= url('student/question_view.php?id=' . (int)$r['id']) ?>"><strong><?= e($r['title']) ?></strong></a></td>
        <td><?= subject_badge($r['subject'], (int)$r['subject_id']) ?></td>
        <td><?= e($r['asker']) ?></td><td><?= (int)$r['answer_count'] ?></td>
        <td><?= e(date('j M Y', strtotime($r['created_at']))) ?></td>
        <td class="text-end">
          <form method="post" action="" data-confirm="Delete this question and its answers?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="delete_question">
            <button class="btn-sc-danger btn-sc-sm" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$questions): ?><tr><td colspan="6" class="empty-state">No questions.</td></tr><?php endif; ?>
    </tbody>
  </table>
<?php else: ?>
  <table class="table-sc">
    <thead><tr><th>Answer</th><th>On question</th><th>By</th><th>Date</th><th class="text-end">Action</th></tr></thead>
    <tbody>
    <?php foreach ($answers as $r): ?>
      <tr>
        <td style="max-width:360px"><?= e(mb_strimwidth($r['body'], 0, 120, '...')) ?></td>
        <td><a href="<?= url('student/question_view.php?id=' . (int)$r['question_id']) ?>"><?= e($r['title']) ?></a></td>
        <td><?= e($r['name']) ?></td>
        <td><?= e(date('j M Y', strtotime($r['created_at']))) ?></td>
        <td class="text-end">
          <form method="post" action="?view=answers" data-confirm="Delete this answer?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="delete_answer">
            <button class="btn-sc-danger btn-sc-sm" type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$answers): ?><tr><td colspan="5" class="empty-state">No answers.</td></tr><?php endif; ?>
    </tbody>
  </table>
<?php endif; ?>
</div></section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
