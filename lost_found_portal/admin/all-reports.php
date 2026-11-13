<?php
/**
 * admin/all-reports.php - Every report; filter by status (Approved / Rejected / ...) and search.
 */
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$db = get_db();

$valid_status = ['PENDING', 'APPROVED', 'REJECTED', 'MATCHED'];
$status = isset($_GET['status']) ? (string)$_GET['status'] : '';
if (!in_array($status, $valid_status, true)) {
    $status = '';
}
$type = isset($_GET['type']) ? (string)$_GET['type'] : '';
$q    = trim(isset($_GET['q']) ? (string)$_GET['q'] : '');

$active_tab = ($status === 'APPROVED') ? 'approved' : (($status === 'REJECTED') ? 'rejected' : 'all');

$where = ['1 = 1'];
$params = [];
if ($status !== '') { $where[] = 'r.status = ?';      $params[] = $status; }
if ($type === 'LOST' || $type === 'FOUND') { $where[] = 'r.report_type = ?'; $params[] = $type; }
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(r.item_name LIKE ? OR r.location LIKE ? OR u.full_name LIKE ?)';
    array_push($params, $like, $like, $like);
}
$stmt = $db->prepare(
    'SELECT r.id, r.item_name, r.report_type, r.status, r.created_at, u.full_name
     FROM reports r JOIN users u ON u.id = r.user_id
     WHERE ' . implode(' AND ', $where) . ' ORDER BY r.created_at DESC, r.id DESC'
);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$back = 'all-reports.php' . ($_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
if (!preg_match('/^[a-z\-]+\.php(\?[A-Za-z0-9=&_%+\-]*)?$/', $back)) {
    $back = 'all-reports.php';
}

$page_title = 'All Reports';
require __DIR__ . '/../includes/header.php';
?>
<h1>Reports</h1>
<?php require __DIR__ . '/../includes/admin-tabs.php'; ?>

<form method="get" action="<?= e(url('admin/all-reports.php')) ?>" class="card filter-bar">
    <div class="form-group">
        <label for="q">Search</label>
        <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Item, location or reporter">
    </div>
    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            <?php foreach ($valid_status as $s): ?>
                <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="type">Type</label>
        <select id="type" name="type">
            <option value="">All</option>
            <option value="LOST"<?= $type === 'LOST' ? ' selected' : '' ?>>Lost</option>
            <option value="FOUND"<?= $type === 'FOUND' ? ' selected' : '' ?>>Found</option>
        </select>
    </div>
    <div class="form-group filter-buttons">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a class="btn btn-outline" href="<?= e(url('admin/all-reports.php')) ?>">Reset</a>
    </div>
</form>

<section class="card">
    <p class="muted"><?= count($reports) ?> report(s)</p>
    <?php if ($reports): ?>
    <div class="table-wrap"><table>
        <thead><tr><th>ID</th><th>Item</th><th>Type</th><th>Reporter</th><th>Submitted</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($reports as $r): ?>
            <tr>
                <td>#<?= (int)$r['id'] ?></td>
                <td><?= e($r['item_name']) ?></td>
                <td><?= e(type_label($r['report_type'])) ?></td>
                <td><?= e($r['full_name']) ?></td>
                <td><?= e(format_date($r['created_at'])) ?></td>
                <td><?= status_badge($r['status']) ?></td>
                <td class="btn-row">
                    <a class="btn btn-sm btn-outline" href="<?= e(url('admin/view-report.php?id=' . (int)$r['id'])) ?>">View</a>
                    <?php if ($r['status'] === 'PENDING' || $r['status'] === 'REJECTED'): ?>
                        <?= action_form('admin/report-action.php', ['report_id' => $r['id'], 'action' => 'approve', 'back' => $back], 'Approve', 'btn-success') ?>
                    <?php endif; ?>
                    <?php if ($r['status'] === 'PENDING' || $r['status'] === 'APPROVED'): ?>
                        <?= action_form('admin/report-action.php', ['report_id' => $r['id'], 'action' => 'reject', 'back' => $back], 'Reject', 'btn-warning', 'Reject this report?') ?>
                    <?php endif; ?>
                    <?= action_form('admin/report-action.php', ['report_id' => $r['id'], 'action' => 'delete', 'back' => $back], 'Delete', 'btn-danger', 'Delete this report permanently?') ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
