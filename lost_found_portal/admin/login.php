<?php
/**
 * admin/login.php - Separate login page for administrators.
 */
require_once __DIR__ . '/../includes/auth.php';

$current = current_user();
if ($current) {
    redirect($current['role'] === 'ADMIN' ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$error = '';
$identifier = '';

if (is_post()) {
    verify_csrf();
    $identifier = trim(isset($_POST['identifier']) ? $_POST['identifier'] : '');
    $password   = isset($_POST['password']) ? $_POST['password'] : '';

    if ($identifier === '' || $password === '') {
        $error = 'Please enter the admin Login ID (or email) and password.';
    } else {
        list($admin, $login_error) = attempt_login($identifier, $password, 'ADMIN');
        if ($admin) {
            login_user($admin);
            redirect('admin/dashboard.php');
        }
        $error = $login_error;
    }
}

$page_title = 'Admin Login';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-box">
    <h1>Administrator Login</h1>
    <p class="muted">Authorized staff only.</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('admin/login.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="identifier">Admin Login ID or Email</label>
            <input type="text" id="identifier" name="identifier" value="<?= e($identifier) ?>" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Log In as Admin</button>
    </form>
    <p class="auth-links"><a href="<?= e(url('index.php')) ?>">&larr; Back to user login</a></p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
