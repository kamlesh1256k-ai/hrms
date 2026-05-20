<?php
// One-time cache clear script - DELETE THIS FILE AFTER USE
if ($_GET['key'] !== 'clear123') { die('Unauthorized'); }

chdir(__DIR__);
$output = [];
exec('php artisan cache:clear 2>&1', $output);
exec('php artisan config:clear 2>&1', $output);
exec('php artisan view:clear 2>&1', $output);
echo '<pre>' . implode("\n", $output) . '</pre>';
echo 'Done!';
