<?php
// auth/login.php: one login for students and admin. Redirects by role.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/flash.php'; // also starts the session

// Already logged in? Go straight to the right dashboard.
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . url(($_SESSION['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit;
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Enter your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, password, role, status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = 'Incorrect email or password.';   // same message for both cases on purpose
        } elseif ($user['status'] !== 'active') {
            $error = 'Your account is inactive. Please contact the administrator.';
        } else {
            session_regenerate_id(true);                // stops session fixation
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            header('Location: ' . url($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
            exit;
        }
    }
}

$pageTitle = 'Login';
$layout    = 'bare';
$hideFlash = true;
include __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <section class="auth-side">
    <a class="sc-brand" href="<?= url('auth/login.php') ?>"><?= brand_logo('sc-logo-sky') ?>StudyCircle</a>
    <h1>Learn together. Share notes. Pass together.</h1>
    <p>NSBM student community for notes, questions and study groups.</p>
    <div class="auth-chips">
      <span class="auth-chip">Share notes</span>
      <span class="auth-chip">Ask questions</span>
      <span class="auth-chip">Join study groups</span>
    </div>
    <img class="auth-illustration" src="<?= url('assets/img/hero-study.svg') ?>" alt="" width="440" height="330">
  </section>

  <section class="auth-main">
    <form class="card-sc auth-card" method="post" action="" novalidate>
      <div>
        <h2>Welcome back</h2>
        <div class="text-muted-sc">Login to continue</div>
      </div>

      <?php show_flash(); ?>
      <?php if ($error !== ''): ?>
        <div class="sc-alert sc-alert-danger" role="alert"><?= e($error) ?></div>
      <?php endif; ?>

      <div>
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" value="<?= e($email) ?>"
               placeholder="you@students.nsbm.ac.lk" autocomplete="email" required autofocus>
      </div>
      <div>
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password"
               autocomplete="current-password" required>
      </div>

      <button class="btn-sc" type="submit">Login</button>
      <div class="text-muted-sc text-center">No account? <a href="<?= url('auth/register.php') ?>">Register</a></div>
    </form>
  </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
