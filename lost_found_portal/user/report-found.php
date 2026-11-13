<?php
/**
 * user/report-found.php - Report a FOUND item (photo required).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$report_type = 'FOUND';
require __DIR__ . '/../includes/report-form.php';
