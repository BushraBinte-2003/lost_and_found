<?php
/**
 * admin/review-claims.php - Admin approves or rejects item claims.
 * Approving a claim sets the item's report status to MATCHED.
 */
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$db = get_db();

if (is_post()) {
    verify_csrf();
    $claim_id = (int)(isset($_POST['claim_id']) ? $_POST['claim_id'] : 0);
    $action   = isset($_POST['action']) ? (string)$_POST['action'] : '';

    $find = $db->prepare('SELECT id, report_id, status FROM claims WHERE id = ?');
    $find->execute([$claim_id]);
    $claim = $find->fetch();

    if (!$claim || $claim['status'] !== 'PENDING') {
        set_flash('error', 'This claim was not found or has already been reviewed.');
    } elseif ($action === 'approve') {
        try {
            $db->beginTransaction();   // all three updates succeed together, or none of them
            $db->prepare("UPDATE claims SET status = 'APPROVED', reviewed_at = NOW() WHERE id = ?")->execute([$claim_id]);
            $match = $db->prepare("UPDATE reports SET status = 'MATCHED' WHERE id = ? AND status = 'APPROVED'");
            $match->execute([$claim['report_id']]);
            if ($match->rowCount() !== 1) {
                throw new RuntimeException('Report is not in APPROVED state.');
            }
            // Other waiting claims for the same item are rejected automatically.
            $db->prepare("UPDATE claims SET status = 'REJECTED', reviewed_at = NOW() WHERE report_id = ? AND id <> ? AND status = 'PENDING'")
               ->execute([$claim['report_id'], $claim_id]);
            $db->commit();
            set_flash('success', 'Claim approved. The item is now marked MATCHED.');
        } catch (Exception $ex) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Claim approve error: ' . $ex->getMessage());
            set_flash('error', 'Could not approve this claim (the item must be in APPROVED status).');
        }
    } elseif ($action === 'reject') {
        $db->prepare("UPDATE claims SET status = 'REJECTED', reviewed_at = NOW() WHERE id = ?")->execute([$claim_id]);
        set_flash('success', 'Claim rejected.');
    } else {
        set_flash('error', 'Unknown action.');
    }
    redirect('admin/review-claims.php');
}

$filter = isset($_GET['status']) ? (string)$_GET['status'] : 'PENDING';
if (!in_array($filter, ['PENDING', 'APPROVED', 'REJECTED', 'ALL'], true)) {
    $filter = 'PENDING';
}
$sql = "SELECT c.*, r.item_name, r.status AS report_status,
               cu.full_name AS claimant_name, cu.email AS claimant_email, cu.phone AS claimant_phone, cu.student_id AS claimant_sid,
               ru.full_name AS reporter_name
        FROM claims c
        JOIN reports r ON r.id = c.report_id
        JOIN users cu ON cu.id = c.claimant_id
        JOIN users ru ON ru.id = r.user_id";
$params = [];
if ($filter !== 'ALL') {
    $sql .= ' WHERE c.status = ?';
    $params[] = $filter;
}
$stmt = $db->prepare($sql . ' ORDER BY c.created_at DESC');
$stmt->execute($params);
$claims = $stmt->fetchAll();

$page_title = 'Review Claims';
require __DIR__ . '/../includes/header.php';
?>
<h1>Item Claims</h1>
<div class="tabs">
<?php foreach (['PENDING' => 'Pending', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected', 'ALL' => 'All'] as $key => $label): ?>
    <a class="tab<?= $filter === $key ? ' active' : '' ?>" href="<?= e(url('admin/review-claims.php?status=' . $key)) ?>"><?= e($label) ?></a>
<?php endforeach; ?>
</div>

<?php if (!$claims): ?>
    <div class="card empty-state">No claims in this list.</div>
<?php endif; ?>

<?php foreach ($claims as $c): ?>
<article class="card">
    <div class="tag-row"><?= status_badge($c['status']) ?> <span class="muted">Claim #<?= (int)$c['id'] ?> &middot; <?= e(format_date($c['created_at'])) ?></span></div>
    <h2>Item: <a href="<?= e(url('admin/view-report.php?id=' . (int)$c['report_id'])) ?>"><?= e($c['item_name']) ?></a> <?= status_badge($c['report_status']) ?></h2>
    <dl class="detail-list">
        <dt>Claimed by</dt><dd><?= e($c['claimant_name']) ?> (ID <?= e($c['claimant_sid']) ?>) &middot; <?= e($c['claimant_email']) ?> &middot; <?= e($c['claimant_phone']) ?></dd>
        <dt>Found item reported by</dt><dd><?= e($c['reporter_name']) ?></dd>
        <dt>Claim description</dt><dd><?= nl2br(e($c['description'])) ?></dd>
        <dt>Private identifying detail</dt><dd><?= e($c['proof_detail']) ?></dd>
    </dl>
    <?php if ($c['status'] === 'PENDING'): ?>
    <div class="btn-row">
        <?= action_form('admin/review-claims.php', ['claim_id' => $c['id'], 'action' => 'approve'], 'Approve claim', 'btn-success', 'Approve this claim and mark the item as MATCHED?') ?>
        <?= action_form('admin/review-claims.php', ['claim_id' => $c['id'], 'action' => 'reject'], 'Reject claim', 'btn-danger', 'Reject this claim?') ?>
    </div>
    <?php endif; ?>
</article>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
