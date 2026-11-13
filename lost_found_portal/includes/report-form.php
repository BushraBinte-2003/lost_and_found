<?php
/**
 * includes/report-form.php
 * Shared by user/report-found.php and user/report-lost.php.
 * The calling page sets $report_type ('LOST' or 'FOUND') and $user first.
 * Difference: a photo is REQUIRED for FOUND reports and OPTIONAL for LOST reports.
 */
$is_found       = ($report_type === 'FOUND');
$form_title     = $is_found ? 'Report a Found Item' : 'Report a Lost Item';
$location_label = $is_found ? 'Found Location' : 'Last Seen Location';
$date_label     = $is_found ? 'Date Found' : 'Date Lost';
$photo_required = $is_found;

$errors = [];
$old = [
    'item_name' => '', 'category' => '', 'description' => '', 'location' => '', 'event_date' => '',
    'contact_name'  => $user['full_name'],
    'contact_phone' => (string)$user['phone'],
    'contact_email' => $user['email'],
];

if (is_post()) {
    verify_csrf();
    foreach ($old as $key => $unused) {
        $old[$key] = trim(isset($_POST[$key]) ? (string)$_POST[$key] : '');
    }

    // ---- Validate text fields ----
    if (mb_strlen($old['item_name']) < 2 || mb_strlen($old['item_name']) > 100) {
        $errors[] = 'Item name must be between 2 and 100 characters.';
    }
    if (!in_array($old['category'], categories(), true)) {
        $errors[] = 'Please choose a category.';
    }
    if (mb_strlen($old['description']) < 10 || mb_strlen($old['description']) > 2000) {
        $errors[] = 'Description must be between 10 and 2000 characters.';
    }
    if (mb_strlen($old['location']) < 2 || mb_strlen($old['location']) > 150) {
        $errors[] = $location_label . ' must be between 2 and 150 characters.';
    }
    if (!valid_past_date($old['event_date'])) {
        $errors[] = $date_label . ' must be a valid date that is not in the future.';
    }
    if (mb_strlen($old['contact_name']) < 2 || mb_strlen($old['contact_name']) > 100) {
        $errors[] = 'Contact person name must be between 2 and 100 characters.';
    }
    if (!valid_phone($old['contact_phone'])) {
        $errors[] = 'Please enter a valid contact phone number.';
    }
    if ($old['contact_email'] !== '' && (!filter_var($old['contact_email'], FILTER_VALIDATE_EMAIL) || mb_strlen($old['contact_email']) > 150)) {
        $errors[] = 'Contact email is not a valid email address.';
    }

    // ---- Validate and save the photo ----
    list($image_path, $image_error) = upload_image(isset($_FILES['photo']) ? $_FILES['photo'] : [], $photo_required);
    if ($image_error) {
        $errors[] = $image_error;
    }

    // ---- Insert into MySQL with status PENDING ----
    if (!$errors) {
        try {
            $insert = get_db()->prepare(
                "INSERT INTO reports (user_id, item_name, report_type, category, description, location,
                                      event_date, image_path, contact_name, contact_phone, contact_email, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING')"
            );
            $insert->execute([
                $user['id'], $old['item_name'], $report_type, $old['category'], $old['description'],
                $old['location'], $old['event_date'], $image_path, $old['contact_name'],
                $old['contact_phone'], ($old['contact_email'] !== '' ? $old['contact_email'] : null),
            ]);
            set_flash('success', 'Your ' . strtolower($report_type) . ' item report was submitted. It is PENDING until an administrator approves it.');
            redirect('user/my-reports.php');
        } catch (PDOException $ex) {
            error_log('Report insert error: ' . $ex->getMessage());
            $errors[] = 'Could not save your report. Please try again.';
        }
    }

    // Something failed: do not keep an orphan image on the disk.
    if ($errors && $image_path) {
        delete_image($image_path);
    }
}

$page_title = $form_title;
require __DIR__ . '/header.php';
?>
<h1><?= e($form_title) ?></h1>
<p class="muted">
    <?= $is_found
        ? 'Your report will be reviewed by an administrator before other users can see it. A photo is required.'
        : 'Your report will be reviewed by an administrator before other users can see it. A photo is optional.' ?>
</p>

<?php if ($errors): ?>
    <div class="alert alert-error"><ul>
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
    <?php if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK): ?>
        <p>Please choose your photo again (browsers cannot keep it after an error).</p>
    <?php endif; ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" action="<?= e(url('user/' . ($is_found ? 'report-found.php' : 'report-lost.php'))) ?>" class="card form-card" novalidate>
    <?= csrf_field() ?>
    <div class="form-grid">
        <div class="form-group">
            <label for="item_name">Item Name</label>
            <input type="text" id="item_name" name="item_name" value="<?= e($old['item_name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="category">Item Category</label>
            <select id="category" name="category" required>
                <option value="">-- Select category --</option>
                <?php foreach (categories() as $cat): ?>
                    <option value="<?= e($cat) ?>"<?= $old['category'] === $cat ? ' selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group form-full">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" required><?= e($old['description']) ?></textarea>
        </div>
        <div class="form-group">
            <label for="location"><?= e($location_label) ?></label>
            <input type="text" id="location" name="location" value="<?= e($old['location']) ?>" required>
        </div>
        <div class="form-group">
            <label for="event_date"><?= e($date_label) ?></label>
            <input type="date" id="event_date" name="event_date" value="<?= e($old['event_date']) ?>" required>
        </div>
        <div class="form-group form-full">
            <label for="photo">Item Photograph <?= $photo_required ? '(required)' : '(optional)' ?></label>
            <input type="file" id="photo" name="photo" class="image-input" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"<?= $photo_required ? ' required' : '' ?>>
            <small class="muted">JPG, JPEG, PNG or WEBP, maximum 5 MB.</small>
            <img id="imagePreview" class="image-preview" alt="Preview" hidden>
        </div>
        <div class="form-group">
            <label for="contact_name">Contact Person's Name</label>
            <input type="text" id="contact_name" name="contact_name" value="<?= e($old['contact_name']) ?>" required>
        </div>
        <div class="form-group">
            <label for="contact_phone">Contact Phone Number</label>
            <input type="tel" id="contact_phone" name="contact_phone" value="<?= e($old['contact_phone']) ?>" required>
        </div>
        <div class="form-group form-full">
            <label for="contact_email">Contact Email (optional)</label>
            <input type="email" id="contact_email" name="contact_email" value="<?= e($old['contact_email']) ?>">
        </div>
    </div>
    <button type="submit" class="btn btn-primary">Submit Report</button>
    <a class="btn btn-outline" href="<?= e(url('user/dashboard.php')) ?>">Cancel</a>
</form>
<?php require __DIR__ . '/footer.php'; ?>
