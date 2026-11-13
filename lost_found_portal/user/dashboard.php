<?php
/**
 * user/dashboard.php - Main dashboard for a logged-in user.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$db = get_db();

// One query counts everything for this user's reports.
$stmt = $db->prepare(
    "SELECT COALESCE(SUM(report_type = 'LOST'), 0)  AS lost_total,
            COALESCE(SUM(report_type = 'FOUND'), 0) AS found_total,
            COALESCE(SUM(status = 'APPROVED'), 0)   AS approved_total,
            COALESCE(SUM(status = 'PENDING'), 0)    AS pending_total
     FROM reports WHERE user_id = ?"
);
$stmt->execute([$user['id']]);
$counts = $stmt->fetch();

$recent = $db->prepare('SELECT id, item_name, report_type, status, created_at FROM reports WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 5');
$recent->execute([$user['id']]);
$recent_reports = $recent->fetchAll();

$page_title = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<h1>Welcome, <?= e($user['full_name']) ?>!</h1>
<p class="muted">What would you like to do today?</p>

<div class="action-cards">
    <a class="action-card action-found" href="<?= e(url('user/report-found.php')) ?>">
        <span class="action-icon">&#128230;</span>
        <h2>Report a Found Item</h2>
        <p>Found something on campus? Upload a photo and help it get back to its owner.</p>
    </a>
    <a class="action-card action-lost" href="<?= e(url('user/report-lost.php')) ?>">
        <span class="action-icon">&#10067;</span>
        <h2>Report a Lost Item</h2>
        <p>Lost something? Tell us about it so others can help you find it.</p>
    </a>
</div>

<div class="stat-grid">
    <div class="stat-card"><span class="stat-number"><?= (int)$counts['lost_total'] ?></span><span class="stat-label">Total Lost Reports</span></div>
    <div class="stat-card"><span class="stat-number"><?= (int)$counts['found_total'] ?></span><span class="stat-label">Total Found Reports</span></div>
    <div class="stat-card"><span class="stat-number"><?= (int)$counts['approved_total'] ?></span><span class="stat-label">Approved Reports</span></div>
    <div class="stat-card"><span class="stat-number"><?= (int)$counts['pending_total'] ?></span><span class="stat-label">Pending Reports</span></div>
</div>

<div class="quick-links">
    <a class="btn btn-outline" href="<?= e(url('user/browse.php')) ?>">Browse Lost &amp; Found Items</a>
    <a class="btn btn-outline" href="<?= e(url('user/my-reports.php')) ?>">My Reports</a>
    <a class="btn btn-outline" href="<?= e(url('user/profile.php')) ?>">My Profile</a>
</div>

<section class="card">
    <h2>Your recent reports</h2>
    <?php if (!$recent_reports): ?>
        <p class="muted">You have not submitted any reports yet.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>ID</th><th>Item</th><th>Type</th><th>Submitted</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recent_reports as $r): ?>
                <tr>
                    <td>#<?= (int)$r['id'] ?></td>
                    <td><a href="<?= e(url('user/item-details.php?id=' . (int)$r['id'])) ?>"><?= e($r['item_name']) ?></a></td>
                    <td><?= e(type_label($r['report_type'])) ?></td>
                    <td><?= e(format_date($r['created_at'])) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
