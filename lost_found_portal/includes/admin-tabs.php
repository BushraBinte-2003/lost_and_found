<?php
// includes/admin-tabs.php - the five report tabs on the admin pages.
// Set $active_tab to: pending_found, pending_lost, approved, rejected or all.
$tabs = [
    'pending_found' => ['admin/pending-reports.php?type=FOUND', 'Pending Found Items'],
    'pending_lost'  => ['admin/pending-reports.php?type=LOST',  'Pending Lost Items'],
    'approved'      => ['admin/all-reports.php?status=APPROVED', 'Approved Reports'],
    'rejected'      => ['admin/all-reports.php?status=REJECTED', 'Rejected Reports'],
    'all'           => ['admin/all-reports.php',                 'All Reports'],
];
?>
<div class="tabs">
<?php foreach ($tabs as $key => $tab): ?>
    <a class="tab<?= $key === $active_tab ? ' active' : '' ?>" href="<?= e(url($tab[0])) ?>"><?= e($tab[1]) ?></a>
<?php endforeach; ?>
</div>
