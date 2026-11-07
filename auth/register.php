<?php
// auth/register.php: create a student account (admins are created in the database or by another admin).
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/flash.php'; // also starts the session

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . url(($_SESSION['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit;
}

$name = $email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    // ----- Server-side validation (never trust only the browser) -----
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = 'Enter your full name (2 to 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Use at least 8 characters, with a letter and a number.';
    }
    if ($password !== $confirm) {
        $errors['confirm'] = 'The passwords do not match.';
    }

    // ----- Email must be unused -----
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }

    // ----- Save -----
    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), 'student', 'active']);

            set_flash('success', 'Account created. You can log in now.');
            header('Location: ' . url('auth/login.php'));
            exit;
        } catch (PDOException $ex) {
            // 23000 = duplicate key (two people registered the same email at once)
            $errors['email'] = $ex->getCode() === '23000'
                ? 'An account with this email already exists.'
                : 'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = 'Register';
$layout    = 'bare';
$hideFlash = true;
include __DIR__ . '/../includes/header.php';

// Small helper so each field prints its error the same way
function field_error(array $errors, string $key): void
{
    if (isset($errors[$key])) {
        echo '<div class="form-text-error" id="' . e($key) . '-error">' . e($errors[$key]) . '</div>';
    }
}
?>
<div class="auth-wrap">
  <section class="auth-side">
    <a class="sc-brand" href="<?= url('auth/login.php') ?>"><?= brand_logo('sc-logo-sky') ?>StudyCircle</a>
    <h1>Join your batch. Study smarter together.</h1>
    <p>Create a free account to download notes, ask questions and join study groups.</p>
    <div class="auth-chips">
      <span class="auth-chip">Download notes</span>
      <span class="auth-chip">Answer questions</span>
      <span class="auth-chip">Plan study sessions</span>
    </div>
    <img class="auth-illustration" src="<?= url('assets/img/hero-study.svg') ?>" alt="" width="440" height="330">
  </section>

  <section class="auth-main">
    <form class="card-sc auth-card" method="post" action="" novalidate>
      <div>
        <h2>Create your account</h2>
        <div class="text-muted-sc">It takes less than a minute</div>
      </div>

      <?php if ($errors): ?>
        <div class="sc-alert sc-alert-danger" role="alert">Please fix the highlighted fields.</div>
      <?php endif; ?>

      <div>
        <label class="form-label" for="name">Full name</label>
        <input class="form-control" type="text" id="name" name="name" value="<?= e($name) ?>"
               autocomplete="name" maxlength="100" required<?= isset($errors['name']) ? ' aria-invalid="true" aria-describedby="name-error"' : '' ?>>
        <?php field_error($errors, 'name'); ?>
      </div>

      <div>
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>"
               placeholder="you@students.nsbm.ac.lk" autocomplete="email" maxlength="150" required<?= isset($errors['email']) ? ' aria-invalid="true" aria-describedby="email-error"' : '' ?>>
        <?php field_error($errors, 'email'); ?>
      </div>

      <div>
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password"
               autocomplete="new-password" minlength="8" required<?= isset($errors['password']) ? ' aria-invalid="true" aria-describedby="password-error"' : '' ?>>
        <div class="text-muted-sc" style="font-size:13px;margin-top:4px">At least 8 characters, with a letter and a number.</div>
        <?php field_error($errors, 'password'); ?>
      </div>

      <div>
        <label class="form-label" for="confirm">Confirm password</label>
        <input class="form-control" type="password" id="confirm" name="confirm"
               autocomplete="new-password" required<?= isset($errors['confirm']) ? ' aria-invalid="true" aria-describedby="confirm-error"' : '' ?>>
        <?php field_error($errors, 'confirm'); ?>
      </div>

      <button class="btn-sc" type="submit">Create account</button>
      <div class="text-muted-sc text-center">Already registered? <a href="<?= url('auth/login.php') ?>">Login</a></div>
    </form>
  </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
