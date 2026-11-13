<?php
/**
 * logout.php - Safe logout. Only a POST request with a valid CSRF token logs you out.
 */
require_once __DIR__ . '/includes/auth.php';

if (!is_post()) {
    redirect('index.php');
}
verify_csrf();

$was_admin = (current_user() && current_user()['role'] === 'ADMIN');
logout_user();

// Start a fresh session just to show the "logged out" message.
session_start();
session_regenerate_id(true);
set_flash('success', 'You have been logged out.');
redirect($was_admin ? 'admin/login.php' : 'index.php');
