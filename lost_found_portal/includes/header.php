<?php
/**
 * includes/header.php
 * Top of every page. Set $page_title before including it.
 */
$nav_user  = current_user();
$nav_flash = get_flash();
$hdr_title = isset($page_title) ? $page_title : 'Lost & Found Portal';
$home_link = $nav_user ? ($nav_user['role'] === 'ADMIN' ? 'admin/dashboard.php' : 'user/dashboard.php') : 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($hdr_title) ?> | Campus Lost &amp; Found</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="<?= e(url($home_link)) ?>"><span class="brand-icon">&#128269;</span> Campus Lost &amp; Found</a>
        <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">&#9776;</button>
        <nav class="main-nav" id="mainNav">
        <?php if ($nav_user && $nav_user['role'] === 'ADMIN'): ?>
            <?= nav_link('admin/dashboard.php', 'Dashboard') ?>
            <?= nav_link('admin/pending-reports.php', 'Pending') ?>
            <?= nav_link('admin/all-reports.php', 'All Reports') ?>
            <?= nav_link('admin/manage-users.php', 'Users') ?>
            <?= nav_link('admin/review-claims.php', 'Claims') ?>
        <?php elseif ($nav_user): ?>
            <?= nav_link('user/dashboard.php', 'Dashboard') ?>
            <?= nav_link('user/browse.php', 'Browse Items') ?>
            <?= nav_link('user/report-found.php', 'Report Found') ?>
            <?= nav_link('user/report-lost.php', 'Report Lost') ?>
            <?= nav_link('user/my-reports.php', 'My Reports') ?>
            <?= nav_link('user/profile.php', 'My Profile') ?>
        <?php else: ?>
            <?= nav_link('index.php', 'Login') ?>
            <?= nav_link('register.php', 'Register') ?>
            <?= nav_link('admin/login.php', 'Admin Login') ?>
        <?php endif; ?>
        <?php if ($nav_user): ?>
            <form method="post" action="<?= e(url('logout.php')) ?>" class="logout-form">
                <?= csrf_field() ?>
                <button type="submit" class="nav-link nav-logout">Logout</button>
            </form>
        <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container main-content">
<?php foreach ($nav_flash as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
