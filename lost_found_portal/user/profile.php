<?php
/**
 * user/profile.php - Update profile details and change password.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$db = get_db();

$profile_errors = [];
$password_errors = [];
$form = ['full_name' => $user['full_name'], 'email' => $user['email'], 'phone' => (string)$user['phone']];

if (is_post()) {
    verify_csrf();
    $which = isset($_POST['form']) ? $_POST['form'] : '';

    if ($which === 'profile') {
        foreach ($form as $key => $unused) {
            $form[$key] = trim(isset($_POST[$key]) ? (string)$_POST[$key] : '');
        }
        if (mb_strlen($form['full_name']) < 3 || mb_strlen($form['full_name']) > 100) {
            $profile_errors[] = 'Full name must be between 3 and 100 characters.';
        }
        if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 150) {
            $profile_errors[] = 'Please enter a valid email address.';
        }
        if (!valid_phone($form['phone'])) {
            $profile_errors[] = 'Please enter a valid phone number.';
        }
        if (!$profile_errors) {
            $dup = $db->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
            $dup->execute([$form['email'], $user['id']]);
            if ($dup->fetch()) {
                $profile_errors[] = 'That email address is used by another account.';
            }
        }
        if (!$profile_errors) {
            try {
                $up = $db->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?');
                $up->execute([$form['full_name'], $form['email'], $form['phone'], $user['id']]);
                set_flash('success', 'Your profile was updated.');
                redirect('user/profile.php');
            } catch (PDOException $ex) {
                error_log('Profile update error: ' . $ex->getMessage());
                $profile_errors[] = 'Could not update your profile. Please try again.';
            }
        }
    } elseif ($which === 'password') {
        $current = isset($_POST['current_password']) ? (string)$_POST['current_password'] : '';
        $new     = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';
        $confirm = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

        $row = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
        $row->execute([$user['id']]);
        $hash = $row->fetchColumn();

        if (!password_verify($current, $hash)) {
            $password_errors[] = 'Your current password is incorrect.';
        }
        if (strlen($new) < 8) {
            $password_errors[] = 'New password must be at least 8 characters long.';
        }
        if ($new !== $confirm) {
            $password_errors[] = 'New password and confirmation do not match.';
        }
        if (!$password_errors) {
            $up = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $up->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);
            set_flash('success', 'Your password was changed.');
            redirect('user/profile.php');
        }
    }
}

$page_title = 'My Profile';
require __DIR__ . '/../includes/header.php';
?>
<h1>My Profile</h1>
<div class="two-col">
    <section class="card">
        <h2>Account details</h2>
        <?php if ($profile_errors): ?>
            <div class="alert alert-error"><ul><?php foreach ($profile_errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('user/profile.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="profile">
            <div class="form-group">
                <label>Student ID (cannot be changed)</label>
                <input type="text" value="<?= e($user['student_id']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>Login ID (cannot be changed)</label>
                <input type="text" value="<?= e($user['login_id']) ?>" disabled>
            </div>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?= e($form['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= e($form['email']) ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?= e($form['phone']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </section>

    <section class="card">
        <h2>Change password</h2>
        <?php if ($password_errors): ?>
            <div class="alert alert-error"><ul><?php foreach ($password_errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('user/profile.php')) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="password">
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password (min. 8 characters)</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
