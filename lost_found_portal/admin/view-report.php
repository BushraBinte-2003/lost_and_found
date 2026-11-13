<?php
/**
 * admin/view-report.php - Admin sees the COMPLETE report, photo and reporter information.
 */
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$db = get_db();

$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$stmt = $db->prepare(
    "SELECT r.*, u.full_name AS reporter_name, u.student_id, u.email AS reporter_email, u.phone AS reporter_phone, u.login_id
     FROM reports r JOIN users u ON u.id = r.user_id WHERE r.id = ?"
);
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) {
    set_flash('error', 'Report not found.');
    redirect('admin/all-reports.php');
}
$back = 'view-report.php?id=' . (int)$r['id'];

$page_title = 'Report #' . $r['id'];
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= e(url('admin/all-reports.php')) ?>">&larr; Back to all reports</a></p>
<article class="card review-card">
    <div class="review-photo"><?= report_image($r['image_path'], $r['item_name']) ?></div>
    <div class="review-info">
        <div class="tag-row">
            <span class="tag tag-<?= e(strtolower($r['report_type'])) ?>"><?= e(type_label($r['report_type'])) ?></span>
            <?= status_badge($r['status']) ?> <span class="muted">Report #<?= (int)$r['id'] ?></span>
        </div>
        <h1><?= e($r['item_name']) ?></h1>
        <dl class="detail-list">
            <dt>Category</dt><dd><?= e($r['category']) ?></dd>
            <dt>Location</dt><dd><?= e($r['location']) ?></dd>
            <dt>Date</dt><dd><?= e(format_date($r['event_date'])) ?></dd>
            <dt>Description</dt><dd><?= nl2br(e($r['description'])) ?></dd>
            <dt>Contact person</dt><dd><?= e($r['contact_name']) ?></dd>
            <dt>Contact phone</dt><dd><?= e($r['contact_phone']) ?></dd>
            <dt>Contact email</dt><dd><?= $r['contact_email'] ? e($r['contact_email']) : '<span class="muted">not provided</span>' ?></dd>
            <dt>Reported by</dt><dd><?= e($r['reporter_name']) ?> (Student ID <?= e($r['student_id']) ?>, login <?= e($r['login_id']) ?>)<br>
                <?= e($r['reporter_email']) ?> &middot; <?= e($r['reporter_phone']) ?></dd>
            <dt>Submitted</dt><dd><?= e(format_date($r['created_at'])) ?></dd>
            <dt>Last updated</dt><dd><?= e(format_date($r['updated_at'])) ?></dd>
        </dl>
        <div class="btn-row">
            <?php if ($r['status'] === 'PENDING' || $r['status'] === 'REJECTED'): ?>
                <?= action_form('admin/report-action.php', ['report_id' => $r['id'], 'action' => 'approve', 'back' => $back], 'Approve', 'btn-success') ?>
            <?php endif; ?>
            <?php if ($r['status'] === 'PENDING' || $r['status'] === 'APPROVED'): ?>
                <?= action_form('admin/report-action.php', ['report_id' => $r['id'], 'action' => 'reject', 'back' => $back], 'Reject', 'btn-warning', 'Reject this report?') ?>
            <?php endif; ?>
            <?= action_form('admin/report-action.php', ['report_id' => $r['id'], 'action' => 'delete', 'back' => $back], 'Delete', 'btn-danger', 'Delete this report permanently?') ?>
        </div>
    </div>
</article>
<?php require __DIR__ . '/../includes/footer.php'; ?>
