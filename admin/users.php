<?php
// admin/users.php: list, add, activate / deactivate, change role, delete users.
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../includes/csrf.php';

$me     = (int)$_SESSION['user_id'];
$errors = [];
$form   = ['name' => '', 'email' => '', 'role' => 'student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'add') {
        $form = ['name' => trim($_POST['name'] ?? ''), 'email' => trim($_POST['email'] ?? ''), 'role' => ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'student'];
        $password = $_POST['password'] ?? '';

        if (mb_strlen($form['name']) < 2 || mb_strlen($form['name']) > 100) {
            $errors['name'] = 'Enter a name (2 to 100 characters).';
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 150) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errors['password'] = 'Use at least 8 characters, with a letter and a number.';
        }
        if (!$errors) {
            try {
                $pdo->prepare('INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$form['name'], $form['email'], password_hash($password, PASSWORD_DEFAULT), $form['role'], 'active']);
                set_flash('success', 'User added.');
                header('Location: ' . url('admin/users.php'));
                exit;
            } catch (PDOException $ex) {
                $errors['email'] = $ex->getCode() === '23000' ? 'This email is already registered.' : 'Something went wrong. Please try again.';
            }
        }
    } elseif ($id === $me) {
        set_flash('danger', 'You cannot change or delete your own account here.');
        header('Location: ' . url('admin/users.php'));
        exit;
    } else {
        if ($action === 'toggle_status') {
            $pdo->prepare("UPDATE users SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?")->execute([$id]);
            set_flash('success', 'User status changed.');
        } elseif ($action === 'toggle_role') {
            $pdo->prepare("UPDATE users SET role = IF(role = 'admin', 'student', 'admin') WHERE id = ?")->execute([$id]);
            set_flash('success', 'User role changed.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            set_flash('success', 'User deleted.');
        }
        header('Location: ' . url('admin/users.php'));
        exit;
    }
}

$q     = trim($_GET['q'] ?? '');
$where = ['1 = 1'];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR email LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
$stmt = $pdo->prepare('SELECT id, name, email, role, status, created_at FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC');
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Users';
$layout    = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1>Users</h1></div>
<div class="row g-4">
  <div class="col-xl-4">
    <form class="card-sc d-flex flex-column gap-3" method="post" action="" novalidate>
      <h2 class="section-title mb-0">Add a user</h2>
      <?= csrf_field() ?><input type="hidden" name="action" value="add">
      <div><label class="form-label" for="name">Name</label>
        <input class="form-control" id="name" name="name" maxlength="100" value="<?= e($form['name']) ?>" required><?= err_msg($errors, 'name') ?></div>
      <div><label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" maxlength="150" value="<?= e($form['email']) ?>" required><?= err_msg($errors, 'email') ?></div>
      <div><label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password" autocomplete="new-password" required><?= err_msg($errors, 'password') ?></div>
      <div><label class="form-label" for="role">Role</label>
        <select class="form-select" id="role" name="role">
          <option value="student"<?= $form['role'] === 'student' ? ' selected' : '' ?>>Student</option>
          <option value="admin"<?= $form['role'] === 'admin' ? ' selected' : '' ?>>Admin</option>
        </select></div>
      <div><button class="btn-sc" type="submit">Add user</button></div>
    </form>
  </div>

  <div class="col-xl-8">
    <form class="card-sc d-flex gap-2 mb-3" method="get" action="" role="search">
      <label class="visually-hidden" for="q">Search users</label>
      <input class="form-control" type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Search by name or email">
      <button class="btn-sc" type="submit">Search</button>
    </form>
    <section class="card-sc"><div class="table-wrap">
      <table class="table-sc">
        <thead><tr><th>User</th><th>Role</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): $self = (int)$u['id'] === $me; ?>
          <tr>
            <td><strong><?= e($u['name']) ?></strong><div class="list-row-meta"><?= e($u['email']) ?></div></td>
            <td><span class="badge-sc <?= $u['role'] === 'admin' ? 'badge-sc-lavender' : '' ?>"><?= e(ucfirst($u['role'])) ?></span></td>
            <td><span class="badge-sc <?= $u['status'] === 'active' ? 'badge-sc-success' : 'badge-sc-danger' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
            <td class="text-end">
              <?php if ($self): ?><span class="list-row-meta">This is you</span><?php else: ?>
                <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
                  <?php foreach ([['toggle_status', $u['status'] === 'active' ? 'Deactivate' : 'Activate', 'btn-sc-outline', ''],
                                  ['toggle_role', $u['role'] === 'admin' ? 'Make student' : 'Make admin', 'btn-sc-outline', 'Change the role of ' . $u['name'] . '?'],
                                  ['delete', 'Delete', 'btn-sc-danger', 'Delete ' . $u['name'] . ' and all their uploads, questions and groups?']] as [$act, $label, $cls, $confirm]): ?>
                    <form method="post" action=""<?= $confirm ? ' data-confirm="' . e($confirm) . '"' : '' ?>>
                      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="action" value="<?= $act ?>">
                      <button class="<?= $cls ?> btn-sc-sm" type="submit"><?= e($label) ?></button>
                    </form>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?><tr><td colspan="4" class="empty-state">No users found.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div></section>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
