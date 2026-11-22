<?php
// student/profile.php: edit my details, change password, and see my activity.
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$userId = (int)$_SESSION['user_id'];
$errors = [];



$stmt = $pdo->prepare('SELECT name, email, password, created_at, profile_image, personal_goal, gender FROM users WHERE id = ?');


$stmt->execute([$userId]);
$user = $stmt->fetch();
$nameValue = $user['name'];




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';



if ($action === 'profile') {
    $nameValue = trim($_POST['name'] ?? '');
    $genderValue = $_POST['gender'] ?? '';

    if (mb_strlen($nameValue) < 2 || mb_strlen($nameValue) > 100) {
        $errors['name'] = 'Enter your full name (2 to 100 characters).';
    }

    if (!in_array($genderValue, ['female', 'male'], true)) {
        $errors['gender'] = 'Please select a valid gender.';
    }

    if (!$errors) {
        $pdo->prepare(
            'UPDATE users SET name = ?, gender = ? WHERE id = ?'
        )->execute([$nameValue, $genderValue, $userId]);

        $_SESSION['name'] = $nameValue;

        set_flash('success', 'Profile updated.');
        header('Location: ' . url('student/profile.php'));
        exit;
    }




    } elseif ($action === 'goal') {
        $personalGoal = trim($_POST['personal_goal'] ?? '');

        if (mb_strlen($personalGoal) > 255) {
            $errors['personal_goal'] = 'Your goal must be 255 characters or fewer.';
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users SET personal_goal = ? WHERE id = ?'
            );
            $stmt->execute([
                $personalGoal === '' ? null : $personalGoal,
                $userId
            ]);

            set_flash('success', 'Your study goal has been saved.');
            header('Location: ' . url('student/profile.php'));
            exit;
        }

} elseif ($action === 'photo') {

    $photo = $_FILES['profile_photo'] ?? null;

    if (!$photo || $photo['error'] !== UPLOAD_ERR_OK) {
        $errors['profile_photo'] = 'Please select a valid photo.';
    } elseif ($photo['size'] > 2 * 1024 * 1024) {
        $errors['profile_photo'] = 'Photo must be smaller than 2 MB.';
    } elseif (!is_uploaded_file($photo['tmp_name'])) {
        $errors['profile_photo'] = 'Invalid upload.';
    } else {
        $mime = (new finfo(FILEINFO_MIME_TYPE))
            ->file($photo['tmp_name']);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        $imageInfo = @getimagesize($photo['tmp_name']);

        if (!isset($allowed[$mime]) || $imageInfo === false) {
            $errors['profile_photo'] = 'Only JPG, PNG and WebP images are allowed.';
        } else {
            $folder = __DIR__ . '/../uploads/profile_pictures/';
            $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];

            if (!is_dir($folder) || !is_writable($folder)) {
                $errors['profile_photo'] = 'Photo storage is unavailable.';
            } elseif (!move_uploaded_file(
                $photo['tmp_name'],
                $folder . $filename
            )) {
                $errors['profile_photo'] = 'Could not save your photo.';
            } else {
                try {
                    $pdo->prepare(
                        'UPDATE users SET profile_image = ? WHERE id = ?'
                    )->execute([$filename, $userId]);

                    set_flash('success', 'Profile photo uploaded.');
                    header('Location: ' . url('student/profile.php'));
                    exit;
                } catch (Throwable $e) {
                    @unlink($folder . $filename);
                    $errors['profile_photo'] = 'Could not update your profile photo.';
                }
            }
        }
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


<!-- Student Profile Photo Upload -->
<form
  class="card-sc d-flex flex-column gap-3 mb-4"
  method="post"
  action=""
  enctype="multipart/form-data"
>
  <h2 class="section-title mb-0">My Profile Photo 📸</h2>

  <?= csrf_field() ?>
  <input type="hidden" name="action" value="photo">

  <div>
    <label class="form-label" for="profile_photo">
      Choose your profile photo
    </label>

    <input
      class="form-control"
      type="file"
      id="profile_photo"
      name="profile_photo"
      accept="image/jpeg,image/png,image/webp"
      required
    >
  </div>

  <?= err_msg($errors, 'profile_photo') ?>

  <div>
    <button class="btn-sc" type="submit">
      Upload Photo
    </button>
  </div>
</form>


<div class="row g-4 profile-content-grid">
  <div class="profile-left-column">
    <form class="card-sc d-flex flex-column gap-3" method="post" action="" novalidate>
      <h2 class="section-title mb-0">My details</h2>
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <div><label class="form-label" for="name">Full name</label>
        <input class="form-control" id="name" name="name" maxlength="100" value="<?= e($nameValue) ?>" required><?= err_msg($errors, 'name') ?></div>
      <div><label class="form-label" for="email">Email</label>
        <input class="form-control" id="email" value="<?= e($user['email']) ?>" disabled></div>

<div>
  <label class="form-label" for="gender">Gender</label>

  <select class="form-control" id="gender" name="gender" required>
    <option value="">Select gender</option>

    <option value="female"
      <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>>
      Female
    </option>

    <option value="male"
      <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>>
      Male
    </option>
  </select>
</div>

      <div class="list-row-meta">Member since <?= e(date('j M Y', strtotime($user['created_at']))) ?></div>
      <div><button class="btn-sc" type="submit">Save details</button></div>
    </form>

    <!-- Personal Study Goal -->
    <form class="card-sc d-flex flex-column gap-3" method="post" action="">
      <h2 class="section-title mb-0">My Study Goal 🎯</h2>

      <?= csrf_field() ?>
      <input type="hidden" name="action" value="goal">

      <div>
        <label class="form-label" for="personal_goal">
          What is your study goal?
        </label>

        <textarea
          class="form-control"
          id="personal_goal"
          name="personal_goal"
          rows="3"
          maxlength="255"
          placeholder="e.g. Improve my programming skills."
        ><?= e($_POST['personal_goal'] ?? ($user['personal_goal'] ?? '')) ?></textarea>

        <?= err_msg($errors, 'personal_goal') ?>
      </div>

      <div>
        <button class="btn-sc" type="submit">
          Save My Goal
        </button>
      </div>
    </form>
    <section class="card-sc">
      <h2 class="section-title">My recent answers</h2>
      <?php if (!$myAnswers): ?><div class="empty-state">You have not answered any questions yet.</div><?php endif; ?>
      <?php foreach ($myAnswers as $r): ?>
        <div class="list-row"><a href="<?= url('student/question_view.php?id=' . (int)$r['question_id']) ?>"><?= e($r['title']) ?></a>
          <span class="list-row-meta"><?= e(date('j M Y', strtotime($r['created_at']))) ?></span></div>
      <?php endforeach; ?>
    </section>




  </div>

  <div class="profile-right-column">
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
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>