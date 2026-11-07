<?php
// student/group_form.php: create a study group or edit one you created (same form).
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$userId = (int)$_SESSION['user_id'];
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$row    = null;

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM study_groups WHERE id = ? AND creator_id = ?');
    $stmt->execute([$id, $userId]);
    $row = $stmt->fetch();
    if (!$row) {
        set_flash('danger', 'That group was not found, or you did not create it.');
        header('Location: ' . url('student/groups.php'));
        exit;
    }
}

$form     = ['name' => $row['name'] ?? '', 'subject_id' => (int)($row['subject_id'] ?? 0), 'description' => $row['description'] ?? ''];
$errors   = [];
$subjects = $pdo->query('SELECT id, name FROM subjects ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form = ['name' => trim($_POST['name'] ?? ''), 'subject_id' => (int)($_POST['subject_id'] ?? 0), 'description' => trim($_POST['description'] ?? '')];

    if (mb_strlen($form['name']) < 3 || mb_strlen($form['name']) > 120) {
        $errors['name'] = 'Enter a group name (3 to 120 characters).';
    }
    if (!array_filter($subjects, fn($s) => (int)$s['id'] === $form['subject_id'])) {
        $errors['subject_id'] = 'Choose a subject.';
    }
    if (mb_strlen($form['description']) > 500) {
        $errors['description'] = 'Keep the description under 500 characters.';
    }

    if (!$errors) {
        if ($row) {
            $pdo->prepare('UPDATE study_groups SET name = ?, subject_id = ?, description = ? WHERE id = ? AND creator_id = ?')
                ->execute([$form['name'], $form['subject_id'], $form['description'], $id, $userId]);
            set_flash('success', 'Group updated.');
        } else {
            $pdo->prepare('INSERT INTO study_groups (name, subject_id, creator_id, description) VALUES (?, ?, ?, ?)')
                ->execute([$form['name'], $form['subject_id'], $userId, $form['description']]);
            $id = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO group_members (group_id, user_id) VALUES (?, ?)')->execute([$id, $userId]); // creator joins automatically
            set_flash('success', 'Group created.');
        }
        header('Location: ' . url('student/group_view.php?id=' . $id));
        exit;
    }
}

$pageTitle = $row ? 'Edit group' : 'Create group';
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1><?= e($pageTitle) ?></h1></div>
<form class="card-sc d-flex flex-column gap-3" style="max-width:720px" method="post" action="" novalidate>
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$id ?>">
  <div><label class="form-label" for="name">Group name</label>
    <input class="form-control" id="name" name="name" maxlength="120" value="<?= e($form['name']) ?>" required><?= err_msg($errors, 'name') ?></div>
  <div><label class="form-label" for="subject_id">Subject</label>
    <select class="form-select" id="subject_id" name="subject_id" required>
      <option value="0">Choose a subject</option>
      <?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>"<?= $form['subject_id'] === (int)$s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?>
    </select><?= err_msg($errors, 'subject_id') ?></div>
  <div><label class="form-label" for="description">Description (optional)</label>
    <textarea class="form-control" id="description" name="description" rows="4" maxlength="500"><?= e($form['description']) ?></textarea><?= err_msg($errors, 'description') ?></div>
  <div class="d-flex gap-2">
    <button class="btn-sc" type="submit"><?= $row ? 'Save changes' : 'Create group' ?></button>
    <a class="btn-sc-outline" href="<?= url($row ? 'student/group_view.php?id=' . $id : 'student/groups.php') ?>">Cancel</a>
  </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
