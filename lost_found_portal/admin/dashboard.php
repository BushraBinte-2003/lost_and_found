<?php
/**
 * admin/dashboard.php - Admin overview with statistics.
 */
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$db = get_db();

$total_users = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'USER'")->fetchColumn();
$rep = $db->query(
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(status = 'PENDING'), 0)  AS pending,
            COALESCE(SUM(status = 'APPROVED'), 0) AS approved,
            COALESCE(SUM(status = 'REJECTED'), 0) AS rejected
     FROM reports"
)->fetch();
$pending_claims = (int)$db->query("SELECT COUNT(*) FROM claims WHERE status = 'PENDING'")->fetchColumn();

$latest = $db->query(
    "SELECT r.id, r.item_name, r.report_type, r.created_at, u.full_name
     FROM reports r JOIN users u ON u.id = r.user_id
     WHERE r.status = 'PENDING' ORDER BY r.created_at ASC LIMIT 5"
)->fetchAll();

$page_title = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
$active_tab = '';
?>
<h1>Admin Dashboard</h1>
<p class="muted">Welcome, <?= e($admin['full_name']) ?>.</p>

<div class="stat-grid stat-grid-5">
    <div class="stat-card"><span class="stat-number"><?= $total_users ?></span><span class="stat-label">Total Users</span></div>
    <div class="stat-card"><span class="stat-number"><?= (int)$rep['total'] ?></span><span class="stat-label">Total Reports</span></div>
    <div class="stat-card stat-warn"><span class="stat-number"><?= (int)$rep['pending'] ?></span><span class="stat-label">Pending Reports</span></div>
    <div class="stat-card stat-ok"><span class="stat-number"><?= (int)$rep['approved'] ?></span><span class="stat-label">Approved Reports</span></div>
    <div class="stat-card stat-bad"><span class="stat-number"><?= (int)$rep['rejected'] ?></span><span class="stat-label">Rejected Reports</span></div>
</div>

<?php require __DIR__ . '/../includes/admin-tabs.php'; ?>

<section class="card">
    <h2>Oldest pending reports</h2>
    <?php if (!$latest): ?>
        <p class="muted">No reports are waiting for review. &#127881;</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>ID</th><th>Item</th><th>Type</th><th>Reporter</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($latest as $r): ?>
                <tr>
                    <td>#<?= (int)$r['id'] ?></td>
                    <td><?= e($r['item_name']) ?></td>
                    <td><?= e(type_label($r['report_type'])) ?></td>
                    <td><?= e($r['full_name']) ?></td>
                    <td><?= e(format_date($r['created_at'])) ?></td>
                    <td><a class="btn btn-sm btn-primary" href="<?= e(url('admin/view-report.php?id=' . (int)$r['id'])) ?>">Review</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>

<?php if ($pending_claims > 0): ?>
    <div class="alert alert-info"><?= $pending_claims ?> claim(s) waiting for review.
        <a href="<?= e(url('admin/review-claims.php')) ?>">Review claims</a></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
