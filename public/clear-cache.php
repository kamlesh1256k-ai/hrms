<?php
// One-time cache clear script — DELETE THIS FILE AFTER USE
$key = $_GET['k'] ?? '';
if ($key !== 'miraix2026') {
    die('Unauthorized');
}

chdir(dirname(__DIR__));
echo "<pre>";
echo shell_exec('php artisan config:clear');
echo shell_exec('php artisan cache:clear');
echo "</pre>";
echo "Done! Ab is file ko delete karo.";
