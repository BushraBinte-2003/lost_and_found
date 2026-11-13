<?php
/**
 * user/my-reports.php - All reports (and claims) submitted by the logged-in user.
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$db = get_db();

$stmt = $db->prepare('SELECT id, item_name, report_type, status, created_at FROM reports WHERE user_id = ? ORDER BY created_at DESC, id DESC');
$stmt->execute([$user['id']]);
$reports = $stmt->fetchAll();

$cl = $db->prepare(
    'SELECT c.id, c.status, c.created_at, r.id AS report_id, r.item_name
     FROM claims c JOIN reports r ON r.id = c.report_id
     WHERE c.claimant_id = ? ORDER BY c.created_at DESC'
);
$cl->execute([$user['id']]);
$my_claims = $cl->fetchAll();

$page_title = 'My Reports';
require __DIR__ . '/../includes/header.php';
?>
<h1>My Reports</h1>
<div class="quick-links">
    <a class="btn btn-primary" href="<?= e(url('user/report-found.php')) ?>">Report a Found Item</a>
    <a class="btn btn-outline" href="<?= e(url('user/report-lost.php')) ?>">Report a Lost Item</a>
</div>

<section class="card">
<?php if (!$reports): ?>
    <p class="muted">You have not submitted any reports yet.</p>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Report ID</th><th>Item</th><th>Type</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($reports as $r): ?>
            <tr>
                <td>#<?= (int)$r['id'] ?></td>
                <td><?= e($r['item_name']) ?></td>
                <td><?= e(type_label($r['report_type'])) ?></td>
                <td><?= e(format_date($r['created_at'])) ?></td>
                <td><?= status_badge($r['status']) ?></td>
                <td><a class="btn btn-sm btn-outline" href="<?= e(url('user/item-details.php?id=' . (int)$r['id'])) ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
</section>

<?php if ($my_claims): ?>
<section class="card">
    <h2>My Claims</h2>
    <div class="table-wrap"><table>
        <thead><tr><th>Claim ID</th><th>Item</th><th>Submitted</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($my_claims as $c): ?>
            <tr>
                <td>#<?= (int)$c['id'] ?></td>
                <td><a href="<?= e(url('user/item-details.php?id=' . (int)$c['report_id'])) ?>"><?= e($c['item_name']) ?></a></td>
                <td><?= e(format_date($c['created_at'])) ?></td>
                <td><?= status_badge($c['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
