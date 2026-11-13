<?php
/**
 * admin/manage-users.php - View, suspend/activate and delete registered USER accounts.
 */
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();
$db = get_db();

if (is_post()) {
    verify_csrf();
    $user_id = (int)(isset($_POST['user_id']) ? $_POST['user_id'] : 0);
    $action  = isset($_POST['action']) ? (string)$_POST['action'] : '';

    // Admin accounts can never be changed from this page.
    $find = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'USER'");
    $find->execute([$user_id]);
    if (!$find->fetch()) {
        set_flash('error', 'User not found.');
    } elseif ($action === 'suspend' || $action === 'activate') {
        $new_status = ($action === 'suspend') ? 'SUSPENDED' : 'ACTIVE';
        $up = $db->prepare('UPDATE users SET status = ? WHERE id = ?');
        $up->execute([$new_status, $user_id]);
        set_flash('success', 'User account is now ' . $new_status . '.');
    } elseif ($action === 'delete') {
        // Remember this user's photos so we can delete the files too.
        $imgs = $db->prepare('SELECT image_path FROM reports WHERE user_id = ? AND image_path IS NOT NULL');
        $imgs->execute([$user_id]);
        $paths = $imgs->fetchAll(PDO::FETCH_COLUMN);

        $del = $db->prepare('DELETE FROM users WHERE id = ?');   // reports and claims are removed by foreign keys
        $del->execute([$user_id]);
        foreach ($paths as $p) {
            delete_image($p);
        }
        set_flash('success', 'User and all of their reports were deleted.');
    } else {
        set_flash('error', 'Unknown action.');
    }
    $keep = ($_SERVER['QUERY_STRING'] !== '') ? '?' . $_SERVER['QUERY_STRING'] : '';
    redirect('admin/manage-users.php' . (preg_match('/^\?[A-Za-z0-9=&_%+\-]*$/', $keep) ? $keep : ''));
}

$q = trim(isset($_GET['q']) ? (string)$_GET['q'] : '');
$where = "u.role = 'USER'";
$params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where .= ' AND (u.full_name LIKE ? OR u.student_id LIKE ? OR u.login_id LIKE ? OR u.email LIKE ?)';
    $params = [$like, $like, $like, $like];
}
$stmt = $db->prepare(
    "SELECT u.*, (SELECT COUNT(*) FROM reports r WHERE r.user_id = u.id) AS report_count
     FROM users u WHERE $where ORDER BY u.created_at DESC"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'Manage Users';
require __DIR__ . '/../includes/header.php';
?>
<h1>Registered Users</h1>
<form method="get" action="<?= e(url('admin/manage-users.php')) ?>" class="card filter-bar">
    <div class="form-group">
        <label for="q">Search users</label>
        <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Name, Student ID, Login ID or email">
    </div>
    <div class="form-group filter-buttons">
        <button type="submit" class="btn btn-primary">Search</button>
        <a class="btn btn-outline" href="<?= e(url('admin/manage-users.php')) ?>">Reset</a>
    </div>
</form>

<section class="card">
    <p class="muted"><?= count($users) ?> user(s)</p>
    <?php if ($users): ?>
    <div class="table-wrap"><table>
        <thead><tr><th>ID</th><th>Name</th><th>Student ID</th><th>Login ID</th><th>Email</th><th>Phone</th><th>Reports</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td>#<?= (int)$u['id'] ?></td>
                <td><?= e($u['full_name']) ?></td>
                <td><?= e($u['student_id']) ?></td>
                <td><?= e($u['login_id']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['phone']) ?></td>
                <td><?= (int)$u['report_count'] ?></td>
                <td><?= e(format_date($u['created_at'])) ?></td>
                <td><span class="badge badge-<?= $u['status'] === 'ACTIVE' ? 'approved' : 'rejected' ?>"><?= e($u['status']) ?></span></td>
                <td class="btn-row">
                    <?php if ($u['status'] === 'ACTIVE'): ?>
                        <?= action_form('admin/manage-users.php' . ($q !== '' ? '?q=' . urlencode($q) : ''), ['user_id' => $u['id'], 'action' => 'suspend'], 'Suspend', 'btn-warning', 'Suspend this user? They will not be able to log in.') ?>
                    <?php else: ?>
                        <?= action_form('admin/manage-users.php' . ($q !== '' ? '?q=' . urlencode($q) : ''), ['user_id' => $u['id'], 'action' => 'activate'], 'Activate', 'btn-success') ?>
                    <?php endif; ?>
                    <?= action_form('admin/manage-users.php' . ($q !== '' ? '?q=' . urlencode($q) : ''), ['user_id' => $u['id'], 'action' => 'delete'], 'Delete', 'btn-danger', 'Delete this user AND all of their reports permanently?') ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
