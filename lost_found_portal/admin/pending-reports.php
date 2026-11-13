<?php
/**
 * admin/pending-reports.php - Review PENDING reports (found tab / lost tab).
 */
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();

$type = (isset($_GET['type']) && $_GET['type'] === 'LOST') ? 'LOST' : 'FOUND';
$active_tab = ($type === 'LOST') ? 'pending_lost' : 'pending_found';
$back = 'pending-reports.php?type=' . $type;

$stmt = get_db()->prepare(
    "SELECT r.*, u.full_name AS reporter_name, u.student_id, u.email AS reporter_email, u.phone AS reporter_phone, u.login_id
     FROM reports r JOIN users u ON u.id = r.user_id
     WHERE r.status = 'PENDING' AND r.report_type = ?
     ORDER BY r.created_at ASC"
);
$stmt->execute([$type]);
$reports = $stmt->fetchAll();

$page_title = 'Pending Reports';
require __DIR__ . '/../includes/header.php';
?>
<h1>Review Reports</h1>
<?php require __DIR__ . '/../includes/admin-tabs.php'; ?>

<?php if (!$reports): ?>
    <div class="card empty-state">No pending <?= e(strtolower(type_label($type))) ?> reports. All caught up!</div>
<?php endif; ?>

<?php foreach ($reports as $r): ?>
<article class="card review-card">
    <div class="review-photo"><?= report_image($r['image_path'], $r['item_name']) ?></div>
    <div class="review-info">
        <div class="tag-row">
            <span class="tag tag-<?= e(strtolower($r['report_type'])) ?>"><?= e(type_label($r['report_type'])) ?></span>
            <?= status_badge($r['status']) ?> <span class="muted">Report #<?= (int)$r['id'] ?></span>
        </div>
        <h2><?= e($r['item_name']) ?></h2>
        <dl class="detail-list">
            <dt>Category</dt><dd><?= e($r['category']) ?></dd>
            <dt>Location</dt><dd><?= e($r['location']) ?></dd>
            <dt>Date</dt><dd><?= e(format_date($r['event_date'])) ?></dd>
            <dt>Description</dt><dd><?= nl2br(e($r['description'])) ?></dd>
            <dt>Contact shown</dt><dd><?= e($r['contact_name']) ?>, <?= e($r['contact_phone']) ?><?= $r['contact_email'] ? ', ' . e($r['contact_email']) : '' ?></dd>
            <dt>Reported by</dt><dd><?= e($r['reporter_name']) ?> (ID <?= e($r['student_id']) ?>, <?= e($r['login_id']) ?>) &middot; <?= e($r['reporter_email']) ?> &middot; <?= e($r['reporter_phone']) ?></dd>
            <dt>Submitted</dt><dd><?= e(format_date($r['created_at'])) ?></dd>
        </dl>
        <div class="btn-row">
            <?= action_form('admin/report-action.php', ['report_id' => $r['id'], 'action' => 'approve', 'back' => $back], 'Approve', 'btn-success') ?>
            <?= action_form('admin/report-action.php', ['report_id' => $r['id'], 'action' => 'reject', 'back' => $back], 'Reject', 'btn-warning', 'Reject this report?') ?>
            <?= action_form('admin/report-action.php', ['report_id' => $r['id'], 'action' => 'delete', 'back' => $back], 'Delete', 'btn-danger', 'Delete this report permanently?') ?>
        </div>
    </div>
</article>
<?php endforeach; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
