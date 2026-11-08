<?php
// admin/subjects.php: add, edit and delete subjects (one page for everything).
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$errors = [];
$editId = (int)($_GET['edit'] ?? 0);
$form   = ['id' => 0, 'code' => '', 'name' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare(
            'SELECT (SELECT COUNT(*) FROM materials WHERE subject_id = ?)
                  + (SELECT COUNT(*) FROM questions WHERE subject_id = ?)
                  + (SELECT COUNT(*) FROM study_groups WHERE subject_id = ?)'
        );
        $stmt->execute([$id, $id, $id]);
        $used = (int)$stmt->fetchColumn();

        if ($used > 0) {
            set_flash('danger', "This subject is used by $used item(s), so it cannot be deleted.");
        } else {
            $pdo->prepare('DELETE FROM subjects WHERE id = ?')->execute([$id]);
            set_flash('success', 'Subject deleted.');
        }
        header('Location: ' . url('admin/subjects.php'));
        exit;
    }

    if ($action === 'save') {
        $form = [
            'id'          => (int)($_POST['id'] ?? 0),
            'code'        => strtoupper(trim($_POST['code'] ?? '')),
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
        ];
        $editId = $form['id'];

        if (!preg_match('/^[A-Z0-9-]{2,20}$/', $form['code'])) {
            $errors['code'] = 'Use 2 to 20 letters, numbers or dashes (for example DBS).';
        }
        if (mb_strlen($form['name']) < 2 || mb_strlen($form['name']) > 120) {
            $errors['name'] = 'Enter a subject name (2 to 120 characters).';
        }
        if (mb_strlen($form['description']) > 255) {
            $errors['description'] = 'Keep the description under 255 characters.';
        }

        if (!$errors) {
            try {
                if ($form['id'] > 0) {
                    $pdo->prepare('UPDATE subjects SET code = ?, name = ?, description = ? WHERE id = ?')
                        ->execute([$form['code'], $form['name'], $form['description'], $form['id']]);
                    set_flash('success', 'Subject updated.');
                } else {
                    $pdo->prepare('INSERT INTO subjects (code, name, description) VALUES (?, ?, ?)')
                        ->execute([$form['code'], $form['name'], $form['description']]);
                    set_flash('success', 'Subject added.');
                }
                header('Location: ' . url('admin/subjects.php'));
                exit;
            } catch (PDOException $ex) {
                $errors['code'] = $ex->getCode() === '23000' ? 'This code is already used.' : 'Something went wrong. Please try again.';
            }
        }
    }
} elseif ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, code, name, description FROM subjects WHERE id = ?');
    $stmt->execute([$editId]);
    $form = $stmt->fetch() ?: $form;
}

$subjects = $pdo->query(
    'SELECT s.*, (SELECT COUNT(*) FROM materials m WHERE m.subject_id = s.id) AS material_count
       FROM subjects s ORDER BY s.name'
)->fetchAll();

$pageTitle = 'Subjects';
$layout    = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Subjects</h1></div>

<div class="row g-4">
  <div class="col-xl-4">
    <form class="card-sc d-flex flex-column gap-3" method="post" action="" novalidate>
      <h2 class="section-title mb-0"><?= $form['id'] ? 'Edit subject' : 'Add a subject' ?></h2>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

      <div>
        <label class="form-label" for="code">Code</label>
        <input class="form-control" id="code" name="code" maxlength="20" value="<?= e($form['code']) ?>" required>
        <?php if (isset($errors['code'])): ?><div class="form-text-error"><?= e($errors['code']) ?></div><?php endif; ?>
      </div>
      <div>
        <label class="form-label" for="name">Name</label>
        <input class="form-control" id="name" name="name" maxlength="120" value="<?= e($form['name']) ?>" required>
        <?php if (isset($errors['name'])): ?><div class="form-text-error"><?= e($errors['name']) ?></div><?php endif; ?>
      </div>
      <div>
        <label class="form-label" for="description">Description (optional)</label>
        <textarea class="form-control" id="description" name="description" rows="3" maxlength="255"><?= e($form['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?><div class="form-text-error"><?= e($errors['description']) ?></div><?php endif; ?>
      </div>

      <div class="d-flex gap-2">
        <button class="btn-sc" type="submit"><?= $form['id'] ? 'Save changes' : 'Add subject' ?></button>
        <?php if ($form['id']): ?><a class="btn-sc-outline" href="<?= url('admin/subjects.php') ?>">Cancel</a><?php endif; ?>
      </div>
    </form>
  </div>

  <div class="col-xl-8">
    <section class="card-sc">
      <div class="table-wrap">
        <table class="table-sc">
          <thead><tr><th>Code</th><th>Name</th><th>Materials</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($subjects as $s): ?>
            <tr>
              <td><?= subject_badge($s['code'], (int)$s['id']) ?></td>
              <td>
                <strong><?= e($s['name']) ?></strong>
                <?php if ($s['description']): ?><div class="list-row-meta"><?= e($s['description']) ?></div><?php endif; ?>
              </td>
              <td><?= (int)$s['material_count'] ?></td>
              <td class="text-end">
                <div class="d-inline-flex gap-2">
                  <a class="btn-sc-outline btn-sc-sm" href="<?= url('admin/subjects.php?edit=' . (int)$s['id']) ?>">Edit</a>
                  <form method="post" action="" data-confirm="Delete the subject <?= e($s['name']) ?>?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button class="btn-sc-danger btn-sc-sm" type="submit">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$subjects): ?><tr><td colspan="4" class="empty-state">No subjects yet. Add the first one.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
