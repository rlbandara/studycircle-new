<?php
// student/question_form.php: ask a new question or edit your own (same form).
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$userId = (int)$_SESSION['user_id'];
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$row    = null;

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM questions WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('danger', 'That question was not found.');
        header('Location: ' . url('student/forum.php'));
        exit;
    }
}

$form     = ['subject_id' => (int)($row['subject_id'] ?? 0), 'title' => $row['title'] ?? '', 'body' => $row['body'] ?? ''];
$errors   = [];
$subjects = $pdo->query('SELECT id, name FROM subjects ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form = ['subject_id' => (int)($_POST['subject_id'] ?? 0), 'title' => trim($_POST['title'] ?? ''), 'body' => trim($_POST['body'] ?? '')];

    if (!array_filter($subjects, fn($s) => (int)$s['id'] === $form['subject_id'])) {
        $errors['subject_id'] = 'Choose a subject.';
    }
    if (mb_strlen($form['title']) < 5 || mb_strlen($form['title']) > 200) {
        $errors['title'] = 'Enter a title (5 to 200 characters).';
    }
    if (mb_strlen($form['body']) < 10 || mb_strlen($form['body']) > 5000) {
        $errors['body'] = 'Describe your question (10 to 5000 characters).';
    }

    if (!$errors) {
        if ($row) {
            $pdo->prepare('UPDATE questions SET subject_id = ?, title = ?, body = ? WHERE id = ? AND user_id = ?')
                ->execute([$form['subject_id'], $form['title'], $form['body'], $id, $userId]);
            set_flash('success', 'Question updated.');
        } else {
            $pdo->prepare('INSERT INTO questions (user_id, subject_id, title, body) VALUES (?, ?, ?, ?)')
                ->execute([$userId, $form['subject_id'], $form['title'], $form['body']]);
            $id = (int)$pdo->lastInsertId();
            set_flash('success', 'Question posted.');
        }
        header('Location: ' . url('student/question_view.php?id=' . $id));
        exit;
    }
}

$pageTitle = $row ? 'Edit question' : 'Ask a question';
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1><?= e($pageTitle) ?></h1></div>
<form class="card-sc d-flex flex-column gap-3" style="max-width:720px" method="post" action="" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$id ?>">
  <div>
    <label class="form-label" for="subject_id">Subject</label>
    <select class="form-select" id="subject_id" name="subject_id" required>
      <option value="0">Choose a subject</option>
      <?php foreach ($subjects as $s): ?>
        <option value="<?= (int)$s['id'] ?>"<?= $form['subject_id'] === (int)$s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?= err_msg($errors, 'subject_id') ?>
  </div>
  <div>
    <label class="form-label" for="title">Title</label>
    <input class="form-control" id="title" name="title" maxlength="200" value="<?= e($form['title']) ?>" required>
    <?= err_msg($errors, 'title') ?>
  </div>
  <div>
    <label class="form-label" for="body">Your question</label>
    <textarea class="form-control" id="body" name="body" rows="6" maxlength="5000" required><?= e($form['body']) ?></textarea>
    <?= err_msg($errors, 'body') ?>
  </div>
  <div class="d-flex gap-2">
    <button class="btn-sc" type="submit"><?= $row ? 'Save changes' : 'Post question' ?></button>
    <a class="btn-sc-outline" href="<?= url($row ? 'student/question_view.php?id=' . $id : 'student/forum.php') ?>">Cancel</a>
  </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
