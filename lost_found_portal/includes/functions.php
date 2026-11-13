<?php
/**
 * includes/functions.php
 * Small helper functions used on many pages (sessions, CSRF, uploads, HTML helpers).
 */

// ---- Start a secure session ------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,      // JavaScript cannot read the session cookie
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---- Basic helpers ---------------------------------------------------------
/** Escape text before printing it in HTML (prevents XSS). */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Build a URL inside the project, e.g. url('user/dashboard.php'). */
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// ---- Flash messages (shown once after a redirect) --------------------------
function set_flash($type, $message)
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash()
{
    $messages = isset($_SESSION['flash']) ? $_SESSION['flash'] : [];
    unset($_SESSION['flash']);
    return $messages;
}

// ---- CSRF protection -------------------------------------------------------
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Put this inside every POST form. */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Call this at the top of every POST handler. */
function verify_csrf()
{
    $sent = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        die('Security check failed (invalid or missing CSRF token). Go back, refresh the page and try again.');
    }
}

// ---- Data lists and validation --------------------------------------------
function categories()
{
    return ['Electronics', 'Books & Stationery', 'ID Card / Documents', 'Wallet / Money',
            'Keys', 'Bags', 'Clothing', 'Accessories', 'Water Bottle / Lunch Box',
            'Sports Items', 'Other'];
}

function valid_phone($phone)
{
    return (bool)preg_match('/^\+?[0-9][0-9\s\-]{6,18}$/', $phone);
}

/** True if $date is a real date in YYYY-MM-DD format and not in the future. */
function valid_past_date($date)
{
    $d = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return false;
    }
    return $d <= new DateTime('tomorrow');   // one day of tolerance for time zones
}

function format_date($date)
{
    return date('d M Y', strtotime($date));
}

// ---- HTML helpers ----------------------------------------------------------
function status_badge($status)
{
    return '<span class="badge badge-' . e(strtolower($status)) . '">' . e($status) . '</span>';
}

function type_label($type)
{
    return $type === 'LOST' ? 'Lost' : 'Found';
}

function status_help($status)
{
    $text = [
        'PENDING'  => 'Waiting for an administrator to review this report. Only you and the admin can see it.',
        'APPROVED' => 'Approved. This report is now visible to all registered users.',
        'REJECTED' => 'Rejected by the administrator. It is hidden from other users.',
        'MATCHED'  => 'This item has been matched with its owner.',
    ];
    return isset($text[$status]) ? $text[$status] : '';
}

/** Photo (click to enlarge) or a clean placeholder when there is no photo. */
function report_image($path, $alt)
{
    if ($path) {
        $src = e(url($path));
        return '<button type="button" class="img-btn" data-lightbox="' . $src . '" aria-label="View full-size photo">'
             . '<img src="' . $src . '" alt="' . e($alt) . '" loading="lazy"></button>';
    }
    return '<div class="placeholder"><span class="placeholder-icon">&#128444;</span><span>No photo provided</span></div>';
}

/** Menu link; gets the "active" class when it is the current page. */
function nav_link($path, $label)
{
    $active = (strpos($_SERVER['SCRIPT_NAME'], '/' . $path) !== false) ? ' active' : '';
    return '<a class="nav-link' . $active . '" href="' . e(url($path)) . '">' . e($label) . '</a>';
}

/** A tiny POST form with one button (used for Approve / Reject / Delete ...). */
function action_form($action_path, array $fields, $label, $class, $confirm = '')
{
    $html = '<form method="post" action="' . e(url($action_path)) . '" class="inline-form"';
    if ($confirm !== '') {
        $html .= ' data-confirm="' . e($confirm) . '"';
    }
    $html .= '>' . csrf_field();
    foreach ($fields as $name => $value) {
        $html .= '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '">';
    }
    return $html . '<button type="submit" class="btn btn-sm ' . e($class) . '">' . e($label) . '</button></form>';
}

// ---- Image upload ----------------------------------------------------------
/**
 * Validate and save an uploaded image.
 * Returns [relative_path_or_null, error_message_or_null].
 * Allowed: JPG, JPEG, PNG, WEBP up to 5 MB. The file gets a random name.
 */
function upload_image(array $file, $required)
{
    $max_size = 5 * 1024 * 1024;

    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $required ? [null, 'Please upload a photograph of the item.'] : [null, null];
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return [null, 'The image is too large. Maximum size is 5 MB.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'The image upload failed. Please try again.'];
    }
    if ($file['size'] > $max_size) {
        return [null, 'The image is too large. Maximum size is 5 MB.'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return [null, 'Invalid upload.'];
    }

    // Check the REAL file content (not just the name the user gave it).
    $info = @getimagesize($file['tmp_name']);
    $allowed_types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if ($info === false || !isset($allowed_types[$info['mime']])) {
        return [null, 'Only JPG, JPEG, PNG and WEBP images are allowed.'];
    }
    $original_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($original_ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return [null, 'Only JPG, JPEG, PNG and WEBP images are allowed.'];
    }

    // Safe unique name; the extension comes from the detected type, never from the user.
    $new_name = bin2hex(random_bytes(16)) . '.' . $allowed_types[$info['mime']];
    $folder = __DIR__ . '/../uploads/';
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $folder . $new_name)) {
        return [null, 'Could not save the image. Check that the uploads folder is writable.'];
    }
    return ['uploads/' . $new_name, null];
}

/** Delete a stored image file (only inside the uploads folder). */
function delete_image($path)
{
    if ($path && strpos($path, 'uploads/') === 0 && basename($path) === substr($path, 8)) {
        $full = __DIR__ . '/../' . $path;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
