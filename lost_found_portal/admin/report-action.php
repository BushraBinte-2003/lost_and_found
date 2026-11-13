<?php
/**
 * admin/report-action.php - Handles Approve / Reject / Delete buttons (POST only, admin only).
 */
require_once __DIR__ . '/../includes/auth.php';
$admin = require_admin();

if (!is_post()) {
    redirect('admin/dashboard.php');
}
verify_csrf();

$report_id = (int)(isset($_POST['report_id']) ? $_POST['report_id'] : 0);
$action    = isset($_POST['action']) ? (string)$_POST['action'] : '';
$back      = isset($_POST['back']) ? (string)$_POST['back'] : '';

// Only allow redirecting back to another admin page (no outside URLs).
if (!preg_match('/^[a-z\-]+\.php(\?[A-Za-z0-9=&_%+\-]*)?$/', $back)) {
    $back = 'dashboard.php';
}

$db = get_db();
$stmt = $db->prepare('SELECT id, image_path, status FROM reports WHERE id = ?');
$stmt->execute([$report_id]);
$report = $stmt->fetch();

if (!$report) {
    set_flash('error', 'Report not found.');
    redirect('admin/' . $back);
}

if ($action === 'approve') {
    $up = $db->prepare("UPDATE reports SET status = 'APPROVED' WHERE id = ? AND status IN ('PENDING','REJECTED')");
    $up->execute([$report_id]);
    set_flash($up->rowCount() ? 'success' : 'error', $up->rowCount() ? "Report #$report_id approved. It is now visible to users." : 'This report could not be approved.');
} elseif ($action === 'reject') {
    $up = $db->prepare("UPDATE reports SET status = 'REJECTED' WHERE id = ? AND status IN ('PENDING','APPROVED')");
    $up->execute([$report_id]);
    set_flash($up->rowCount() ? 'success' : 'error', $up->rowCount() ? "Report #$report_id rejected. It is hidden from other users." : 'This report could not be rejected.');
} elseif ($action === 'delete') {
    $del = $db->prepare('DELETE FROM reports WHERE id = ?');   // claims are removed automatically (foreign key)
    $del->execute([$report_id]);
    delete_image($report['image_path']);
    set_flash('success', "Report #$report_id was deleted.");
    if (strpos($back, 'view-report.php') === 0) {
        $back = 'all-reports.php';       // the page we came from no longer exists
    }
} else {
    set_flash('error', 'Unknown action.');
}
redirect('admin/' . $back);
