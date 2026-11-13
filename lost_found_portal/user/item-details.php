<?php
/**
 * user/item-details.php - Full details of ONE report.
 * Visible to: everyone logged in (only if APPROVED) and to the owner (any status).
 * Contact information is shown only for APPROVED reports (or to the owner).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$db = get_db();

$id = (int)(isset($_GET['id']) ? $_GET['id'] : 0);
$stmt = $db->prepare('SELECT * FROM reports WHERE id = ?');
$stmt->execute([$id]);
$r = $stmt->fetch();

$is_owner = $r && ((int)$r['user_id'] === (int)$user['id']);
if (!$r || (!$is_owner && $r['status'] !== 'APPROVED')) {
    // Pending/rejected reports of other people look exactly like "does not exist".
    http_response_code(404);
    $page_title = 'Not found';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="card empty-state"><h2>Report not found</h2><p>This report does not exist or is not available.</p>'
       . '<a class="btn btn-primary" href="' . e(url('user/browse.php')) . '">Browse items</a></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$show_contact = ($r['status'] === 'APPROVED' || $is_owner);

// ---- Claims data ----
$my_claim = null;
$claims_on_item = [];
if ($r['report_type'] === 'FOUND') {
    if (!$is_owner) {
        $c = $db->prepare('SELECT * FROM claims WHERE report_id = ? AND claimant_id = ?');
        $c->execute([$r['id'], $user['id']]);
        $my_claim = $c->fetch();
    } else {
        // The original reporter can read the claims made on their found item.
        $c = $db->prepare(
            'SELECT c.*, u.full_name FROM claims c JOIN users u ON u.id = c.claimant_id
             WHERE c.report_id = ? ORDER BY c.created_at DESC'
        );
        $c->execute([$r['id']]);
        $claims_on_item = $c->fetchAll();
    }
}

$page_title = $r['item_name'];
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= e(url($is_owner ? 'user/my-reports.php' : 'user/browse.php')) ?>">&larr; Back</a></p>

<?php if ($is_owner && $r['status'] !== 'APPROVED'): ?>
    <div class="alert alert-<?= $r['status'] === 'REJECTED' ? 'error' : 'info' ?>">
        Status: <?= status_badge($r['status']) ?> &mdash; <?= e(status_help($r['status'])) ?>
    </div>
<?php endif; ?>

<div class="details-layout">
    <div class="details-photo card"><?= report_image($r['image_path'], $r['item_name']) ?>
        <?php if ($r['image_path']): ?><small class="muted">Click the photo to enlarge it.</small><?php endif; ?>
    </div>

    <div class="details-info card">
        <div class="tag-row">
            <span class="tag tag-<?= e(strtolower($r['report_type'])) ?>"><?= e(type_label($r['report_type'])) ?></span>
            <?= status_badge($r['status']) ?>
        </div>
        <h1><?= e($r['item_name']) ?></h1>
        <dl class="detail-list">
            <dt>Category</dt><dd><?= e($r['category']) ?></dd>
            <dt><?= $r['report_type'] === 'FOUND' ? 'Found Location' : 'Last Seen Location' ?></dt><dd><?= e($r['location']) ?></dd>
            <dt><?= $r['report_type'] === 'FOUND' ? 'Date Found' : 'Date Lost' ?></dt><dd><?= e(format_date($r['event_date'])) ?></dd>
            <dt>Date Reported</dt><dd><?= e(format_date($r['created_at'])) ?></dd>
            <dt>Description</dt><dd><?= nl2br(e($r['description'])) ?></dd>
        </dl>

        <?php if ($show_contact): ?>
        <div class="contact-box">
            <h2><?= $r['report_type'] === 'FOUND' ? 'Contact the finder' : 'Contact the owner' ?></h2>
            <p><strong>Name:</strong> <?= e($r['contact_name']) ?><br>
               <strong>Phone:</strong> <?= e($r['contact_phone']) ?>
               <?php if ($r['contact_email']): ?><br><strong>Email:</strong> <?= e($r['contact_email']) ?><?php endif; ?></p>
            <a class="btn btn-primary" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $r['contact_phone'])) ?>">&#128222; Call Now</a>
            <?php if ($r['contact_email']): ?>
                <a class="btn btn-outline" href="mailto:<?= e($r['contact_email']) ?>">&#9993; Email</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($r['report_type'] === 'FOUND' && !$is_owner && $r['status'] === 'APPROVED'): ?>
            <div class="claim-box">
                <?php if ($my_claim): ?>
                    <p>Your claim on this item: <?= status_badge($my_claim['status']) ?></p>
                <?php else: ?>
                    <p>Is this your item?</p>
                    <a class="btn btn-success" href="<?= e(url('user/claim.php?report_id=' . (int)$r['id'])) ?>">Claim this item</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($is_owner && $r['report_type'] === 'FOUND'): ?>
<section class="card">
    <h2>Claims on your found item</h2>
    <?php if (!$claims_on_item): ?>
        <p class="muted">Nobody has claimed this item yet.</p>
    <?php else: ?>
        <p class="muted">The administrator makes the final decision on each claim.</p>
        <?php foreach ($claims_on_item as $c): ?>
            <div class="claim-item">
                <p><strong><?= e($c['full_name']) ?></strong> &middot; <?= e(format_date($c['created_at'])) ?> &middot; <?= status_badge($c['status']) ?></p>
                <p><strong>Claim:</strong> <?= nl2br(e($c['description'])) ?></p>
                <p><strong>Private identifying detail:</strong> <?= e($c['proof_detail']) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
