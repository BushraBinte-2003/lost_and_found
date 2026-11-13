<?php
/**
 * register.php - Public registration. It ALWAYS creates a normal USER account.
 */
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect('index.php');
}

$errors = [];
$old = ['full_name' => '', 'student_id' => '', 'email' => '', 'phone' => '', 'login_id' => ''];

if (is_post()) {
    verify_csrf();
    foreach ($old as $key => $unused) {
        $old[$key] = trim(isset($_POST[$key]) ? (string)$_POST[$key] : '');
    }
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $confirm  = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

    // ---- Validation (always done on the server) ----
    if (mb_strlen($old['full_name']) < 3 || mb_strlen($old['full_name']) > 100) {
        $errors[] = 'Full name must be between 3 and 100 characters.';
    }
    if (!preg_match('/^[A-Za-z0-9\-]{3,30}$/', $old['student_id'])) {
        $errors[] = 'Student ID must be 3-30 characters (letters, numbers and dashes only).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['email']) > 150) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!valid_phone($old['phone'])) {
        $errors[] = 'Please enter a valid phone number (digits, optional + at the start).';
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

    // ---- Duplicate check (Login ID, email, Student ID must be unique) ----
    if (!$errors) {
        $db = get_db();
        $check = $db->prepare('SELECT login_id, email, student_id FROM users WHERE login_id = ? OR email = ? OR student_id = ?');
        $check->execute([$old['login_id'], $old['email'], $old['student_id']]);
        foreach ($check->fetchAll() as $row) {
            if (strcasecmp($row['login_id'], $old['login_id']) === 0) {
                $errors[] = 'This Login ID is already taken.';
            }
            if (strcasecmp($row['email'], $old['email']) === 0) {
                $errors[] = 'This email address is already registered.';
            }
            if ($row['student_id'] !== null && strcasecmp($row['student_id'], $old['student_id']) === 0) {
                $errors[] = 'This Student ID is already registered.';
            }
        }
        $errors = array_values(array_unique($errors));
    }

    // ---- Save the new user in MySQL ----
    if (!$errors) {
        try {
            $insert = $db->prepare(
                "INSERT INTO users (full_name, student_id, email, phone, login_id, password_hash, role)
                 VALUES (?, ?, ?, ?, ?, ?, 'USER')"      // role is fixed: nobody can register as admin
            );
            $insert->execute([
                $old['full_name'], $old['student_id'], $old['email'], $old['phone'],
                $old['login_id'], password_hash($password, PASSWORD_DEFAULT),
            ]);
            set_flash('success', 'Registration successful! You can now log in with your Login ID or email.');
            redirect('index.php');
        } catch (PDOException $ex) {
            if (isset($ex->errorInfo[1]) && (int)$ex->errorInfo[1] === 1062) {
                $errors[] = 'Login ID, email or Student ID is already registered.';
            } else {
                error_log('Register error: ' . $ex->getMessage());
                $errors[] = 'Something went wrong while saving your account. Please try again.';
            }
        }
    }
}

$page_title = 'Register';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-box auth-wide">
    <h1>Create your account</h1>
    <p class="muted">All fields are required.</p>
    <?php if ($errors): ?>
        <div class="alert alert-error"><ul>
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('register.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="student_id">Student ID</label>
                <input type="text" id="student_id" name="student_id" value="<?= e($old['student_id']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>" required>
            </div>
            <div class="form-group">
                <label for="login_id">Login ID</label>
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
        <button type="submit" class="btn btn-primary btn-block">Register</button>
    </form>
    <p class="auth-links">Already have an account? <a href="<?= e(url('index.php')) ?>">Log in</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
