<?php
/**
 * setup/create_admin.php - Run ONCE in the browser to create the first administrator.
 *  - The password you type is stored with password_hash() (never in plain text).
 *  - It refuses to run if an admin already exists.
 *  - After success, DELETE the "setup" folder.
 */
require_once __DIR__ . '/../includes/auth.php';

$db = get_db();
$admin_exists = ((int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'ADMIN'")->fetchColumn()) > 0;

$errors = [];
$created = false;
$old = ['full_name' => '', 'email' => '', 'login_id' => ''];

if (!$admin_exists && is_post()) {
    verify_csrf();
    foreach ($old as $key => $unused) {
        $old[$key] = trim(isset($_POST[$key]) ? (string)$_POST[$key] : '');
    }
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $confirm  = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

    if (mb_strlen($old['full_name']) < 3 || mb_strlen($old['full_name']) > 100) {
        $errors[] = 'Full name must be between 3 and 100 characters.';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^[A-Za-z0-9_]{4,30}$/', $old['login_id'])) {
        $errors[] = 'Login ID must be 4-30 characters (letters, numbers and underscore only).';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Password and Confirm Password do not match.';
    }

    if (!$errors) {
        try {
            $insert = $db->prepare(
                "INSERT INTO users (full_name, student_id, email, phone, login_id, password_hash, role)
                 VALUES (?, NULL, ?, NULL, ?, ?, 'ADMIN')"
            );
            $insert->execute([$old['full_name'], $old['email'], $old['login_id'], password_hash($password, PASSWORD_DEFAULT)]);
            $created = true;
        } catch (PDOException $ex) {
            if (isset($ex->errorInfo[1]) && (int)$ex->errorInfo[1] === 1062) {
                $errors[] = 'That Login ID or email is already used by another account.';
            } else {
                error_log('Create admin error: ' . $ex->getMessage());
                $errors[] = 'Could not create the admin account. Did you import the SQL file?';
            }
        }
    }
}

$page_title = 'Create Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-box auth-wide">
    <h1>First-time Admin Setup</h1>

<?php if ($created): ?>
    <div class="alert alert-success">Admin account created successfully!</div>
    <p>You can now <a href="<?= e(url('admin/login.php')) ?>">log in as admin</a>.</p>
    <p class="alert alert-warning"><strong>Important:</strong> now delete the <code>setup</code> folder from <code>htdocs/lost_found_portal</code> for safety.</p>

<?php elseif ($admin_exists): ?>
    <div class="alert alert-warning">An administrator account already exists, so this setup page is disabled.</div>
    <p>Please delete the <code>setup</code> folder. <a href="<?= e(url('admin/login.php')) ?>">Go to admin login</a>.</p>

<?php else: ?>
    <p class="muted">Create the administrator account. Choose a strong password - it will be stored securely (hashed).</p>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul>
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('setup/create_admin.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group">
                <label for="full_name">Admin Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
            </div>
            <div class="form-group">
                <label for="login_id">Admin Login ID</label>
                <input type="text" id="login_id" name="login_id" value="<?= e($old['login_id']) ?>" required>
            </div>
            <div class="form-group"></div>
            <div class="form-group">
                <label for="password">Password (min. 8 characters)</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create Admin Account</button>
    </form>
<?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
