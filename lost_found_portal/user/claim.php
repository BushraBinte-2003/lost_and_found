<?php
/**
 * user/claim.php - Claim an approved FOUND item ("this is mine").
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$db = get_db();

$report_id = (int)(isset($_REQUEST['report_id']) ? $_REQUEST['report_id'] : 0);
$stmt = $db->prepare("SELECT id, user_id, item_name, report_type, status FROM reports WHERE id = ?");
$stmt->execute([$report_id]);
$r = $stmt->fetch();

// Only approved FOUND items made by somebody else can be claimed.
if (!$r || $r['report_type'] !== 'FOUND' || $r['status'] !== 'APPROVED' || (int)$r['user_id'] === (int)$user['id']) {
    set_flash('error', 'This item cannot be claimed.');
    redirect('user/browse.php');
}

$existing = $db->prepare('SELECT id FROM claims WHERE report_id = ? AND claimant_id = ?');
$existing->execute([$r['id'], $user['id']]);
if ($existing->fetch()) {
    set_flash('info', 'You have already submitted a claim for this item.');
    redirect('user/item-details.php?id=' . (int)$r['id']);
}

$errors = [];
$old = ['description' => '', 'proof_detail' => ''];

if (is_post()) {
    verify_csrf();
    foreach ($old as $key => $unused) {
        $old[$key] = trim(isset($_POST[$key]) ? (string)$_POST[$key] : '');
    }
    if (mb_strlen($old['description']) < 10 || mb_strlen($old['description']) > 2000) {
        $errors[] = 'Claim description must be between 10 and 2000 characters.';
    }
    if (mb_strlen($old['proof_detail']) < 3 || mb_strlen($old['proof_detail']) > 255) {
        $errors[] = 'Please give a private identifying detail (3-255 characters).';
    }
    if (!$errors) {
        try {
            $insert = $db->prepare("INSERT INTO claims (report_id, claimant_id, description, proof_detail, status) VALUES (?, ?, ?, ?, 'PENDING')");
            $insert->execute([$r['id'], $user['id'], $old['description'], $old['proof_detail']]);
            set_flash('success', 'Your claim was submitted. The administrator will review it.');
            redirect('user/my-reports.php');
        } catch (PDOException $ex) {
            error_log('Claim insert error: ' . $ex->getMessage());
            $errors[] = 'Could not save your claim. Please try again.';
        }
    }
}

$page_title = 'Claim Item';
require __DIR__ . '/../includes/header.php';
?>
<h1>Claim: <?= e($r['item_name']) ?></h1>
<p class="muted">Explain why this item is yours. Only the reporter and the administrator can read your private detail.</p>
<?php if ($errors): ?>
    <div class="alert alert-error"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<form method="post" action="<?= e(url('user/claim.php')) ?>" class="card form-card" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
    <div class="form-group">
        <label for="description">Claim description</label>
        <textarea id="description" name="description" rows="4" required><?= e($old['description']) ?></textarea>
    </div>
    <div class="form-group">
        <label for="proof_detail">Private identifying detail (only the owner would know)</label>
        <input type="text" id="proof_detail" name="proof_detail" value="<?= e($old['proof_detail']) ?>" placeholder="e.g. a scratch on the back, wallpaper, contents" required>
    </div>
    <button type="submit" class="btn btn-primary">Submit Claim</button>
    <a class="btn btn-outline" href="<?= e(url('user/item-details.php?id=' . (int)$r['id'])) ?>">Cancel</a>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
