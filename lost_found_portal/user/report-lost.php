<?php
/**
 * user/report-lost.php - Report a LOST item (photo optional).
 */
require_once __DIR__ . '/../includes/auth.php';
$user = require_user();
$report_type = 'LOST';
require __DIR__ . '/../includes/report-form.php';
