<?php
/**
 * index.php - User login page (the home page of the portal).
 */
require_once __DIR__ . '/includes/auth.php';

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
        $error = 'Please enter your Login ID (or email) and your password.';
    } else {
        list($user, $login_error) = attempt_login($identifier, $password, 'USER');
        if ($user) {
            login_user($user);
            redirect('user/dashboard.php');
        }
        $error = $login_error;
    }
}

$page_title = 'User Login';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-box">
    <h1>Welcome back</h1>
    <p class="muted">Log in to report or find lost items on campus.</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('index.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="identifier">Login ID or Email</label>
            <input type="text" id="identifier" name="identifier" value="<?= e($identifier) ?>" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>
    <p class="auth-links">New here? <a href="<?= e(url('register.php')) ?>">Create an account</a><br>
        Administrator? <a href="<?= e(url('admin/login.php')) ?>">Admin login</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
