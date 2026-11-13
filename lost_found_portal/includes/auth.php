<?php
/**
 * includes/auth.php
 * Login, logout and page protection. Every page includes this file first.
 * The role is always re-checked in the database, never trusted from the browser.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/** The logged-in user (row from the users table) or null. */
function current_user()
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $user = null;
    if (!empty($_SESSION['user_id'])) {
        $stmt = get_db()->prepare(
            'SELECT id, full_name, student_id, email, phone, login_id, role, status FROM users WHERE id = ?'
        );
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();
        if ($row && $row['status'] === 'ACTIVE') {
            $user = $row;
        } else {
            unset($_SESSION['user_id'], $_SESSION['role']);   // deleted or suspended account
        }
    }
    return $user;
}

/** Only normal users may open the page. Admins are sent to their own dashboard. */
function require_user()
{
    $user = current_user();
    if (!$user) {
        set_flash('error', 'Please log in to continue.');
        redirect('index.php');
    }
    if ($user['role'] !== 'USER') {
        redirect('admin/dashboard.php');
    }
    return $user;
}

/** Only administrators may open the page. */
function require_admin()
{
    $user = current_user();
    if (!$user) {
        set_flash('error', 'Please log in as an administrator.');
        redirect('admin/login.php');
    }
    if ($user['role'] !== 'ADMIN') {
        http_response_code(403);
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Access denied</title></head>'
          . '<body style="font-family:Arial,sans-serif;text-align:center;margin-top:80px">'
          . '<h1>403 - Access denied</h1><p>This area is for administrators only.</p>'
          . '<p><a href="' . e(url('user/dashboard.php')) . '">Back to my dashboard</a></p></body></html>');
    }
    return $user;
}

/**
 * Check login details. $role is 'USER' or 'ADMIN' (separate login pages).
 * Returns [user_row_or_null, error_message_or_null].
 */
function attempt_login($identifier, $password, $role)
{
    $stmt = get_db()->prepare(
        'SELECT * FROM users WHERE (login_id = ? OR email = ?) AND role = ? LIMIT 1'
    );
    $stmt->execute([$identifier, $identifier, $role]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($password, $row['password_hash'])) {
        return [null, 'Invalid Login ID / email or password.'];
    }
    if ($row['status'] !== 'ACTIVE') {
        return [null, 'This account has been suspended. Please contact the administrator.'];
    }
    return [$row, null];
}

function login_user(array $user)
{
    session_regenerate_id(true);          // stops session fixation
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role']    = $user['role'];
}

function logout_user()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
