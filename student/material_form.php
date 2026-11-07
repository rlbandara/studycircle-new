<?php
// student/material_form.php: upload a new material, or edit one of your own (same form).
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$userId   = (int)$_SESSION['user_id'];
$id       = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$allowed  = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'zip'];
$maxBytes = MAX_UPLOAD_MB * 1024 * 1024;
$material = null;

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM materials WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    $material = $stmt->fetch();
    if (!$material) {
        set_flash('danger', 'That material was not found.');
        header('Location: ' . url('student/my_materials.php'));
        exit;
    }
}

$form   = [
    'title'       => $material['title'] ?? '',
    'subject_id'  => (int)($material['subject_id'] ?? 0),
    'description' => $material['description'] ?? '',
];
$errors = [];
$subjects = $pdo->query('SELECT id, name FROM subjects ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form = [
        'title'       => trim($_POST['title'] ?? ''),
        'subject_id'  => (int)($_POST['subject_id'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
    ];

    if (mb_strlen($form['title']) < 3 || mb_strlen($form['title']) > 150) {
        $errors['title'] = 'Enter a title (3 to 150 characters).';
    }
    $validSubject = array_filter($subjects, fn($s) => (int)$s['id'] === $form['subject_id']);
    if (!$validSubject) {
        $errors['subject_id'] = 'Choose a subject.';
    }
    if (mb_strlen($form['description']) > 2000) {
        $errors['description'] = 'Keep the description under 2000 characters.';
    }

    // ----- File checks -----
    $file    = $_FILES['file'] ?? null;
    $hasFile = $file && $file['error'] !== UPLOAD_ERR_NO_FILE;
    $ext     = '';
    if (!$hasFile && !$material) {
        $errors['file'] = 'Choose a file to upload.';
    } elseif ($hasFile) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['file'] = 'The upload failed. Please try again.';
        } elseif ($file['size'] > $maxBytes) {
            $errors['file'] = 'The file is too large. The limit is ' . MAX_UPLOAD_MB . ' MB.';
        } elseif (!in_array($ext, $allowed, true)) {
            $errors['file'] = 'Allowed types: ' . implode(', ', $allowed) . '.';
        }
    }

    if (!$errors) {
        $newStored = null;
        $newName   = null;
        if ($hasFile) {
            $newStored = bin2hex(random_bytes(16)) . '.' . $ext;   // random name, original name kept in the database
            $newName   = mb_substr(basename($file['name']), 0, 255);
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newStored)) {
                $errors['file'] = 'Could not save the file. Check that the uploads folder exists and is writable.';
            }
        }
    }

    if (!$errors) {
        if ($material) {
            // Editing sends the material back for approval
            if ($newStored) {
                $pdo->prepare("UPDATE materials SET title=?, subject_id=?, description=?, file_name=?, file_path=?, status='pending' WHERE id=? AND user_id=?")
                    ->execute([$form['title'], $form['subject_id'], $form['description'], $newName, $newStored, $id, $userId]);
                $old = basename($material['file_path']);
                if ($old !== '' && is_file(UPLOAD_DIR . $old)) {
                    unlink(UPLOAD_DIR . $old);
                }
            } else {
                $pdo->prepare("UPDATE materials SET title=?, subject_id=?, description=?, status='pending' WHERE id=? AND user_id=?")
                    ->execute([$form['title'], $form['subject_id'], $form['description'], $id, $userId]);
            }
            set_flash('success', 'Material updated. It will be visible again after the admin approves it.');
        } else {
            $pdo->prepare("INSERT INTO materials (user_id, subject_id, title, description, file_name, file_path, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')")
                ->execute([$userId, $form['subject_id'], $form['title'], $form['description'], $newName, $newStored]);
            set_flash('success', 'Material uploaded. It will appear after the admin approves it.');
        }
        header('Location: ' . url('student/my_materials.php'));
        exit;
    }
}

$pageTitle = $material ? 'Edit material' : 'Upload material';
$layout    = 'student';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1><?= $material ? 'Edit material' : 'Upload material' ?></h1></div>

<form class="card-sc d-flex flex-column gap-3" style="max-width:720px" method="post" action="" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$id ?>">

  <div>
    <label class="form-label" for="title">Title</label>
    <input class="form-control" id="title" name="title" maxlength="150" value="<?= e($form['title']) ?>" required>
    <?php if (isset($errors['title'])): ?><div class="form-text-error"><?= e($errors['title']) ?></div><?php endif; ?>
  </div>

  <div>
    <label class="form-label" for="subject_id">Subject</label>
    <select class="form-select" id="subject_id" name="subject_id" required>
      <option value="0">Choose a subject</option>
      <?php foreach ($subjects as $s): ?>
        <option value="<?= (int)$s['id'] ?>"<?= $form['subject_id'] === (int)$s['id'] ? ' selected' : '' ?>><?= e($s['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (isset($errors['subject_id'])): ?><div class="form-text-error"><?= e($errors['subject_id']) ?></div><?php endif; ?>
  </div>

  <div>
    <label class="form-label" for="description">Description (optional)</label>
    <textarea class="form-control" id="description" name="description" rows="4" maxlength="2000"><?= e($form['description']) ?></textarea>
    <?php if (isset($errors['description'])): ?><div class="form-text-error"><?= e($errors['description']) ?></div><?php endif; ?>
  </div>

  <div>
    <label class="form-label" for="file"><?= $material ? 'Replace file (optional)' : 'File' ?></label>
    <input class="form-control" type="file" id="file" name="file" accept=".<?= e(implode(',.', $allowed)) ?>"<?= $material ? '' : ' required' ?>>
    <div class="text-muted-sc" style="font-size:13px;margin-top:4px">
      <?php if ($material): ?>Current file: <?= e($material['file_name']) ?>. <?php endif; ?>
      PDF, Word, PowerPoint, Excel, TXT or ZIP, up to <?= MAX_UPLOAD_MB ?> MB.
    </div>
    <?php if (isset($errors['file'])): ?><div class="form-text-error"><?= e($errors['file']) ?></div><?php endif; ?>
  </div>

  <div class="d-flex gap-2">
    <button class="btn-sc" type="submit"><?= $material ? 'Save changes' : 'Upload' ?></button>
    <a class="btn-sc-outline" href="<?= url('student/my_materials.php') ?>">Cancel</a>
  </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
