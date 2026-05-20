<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check ALL logo settings
$rows = \DB::table('settings')->where('name', 'company_logo')->get();
echo "All logo settings:\n";
foreach ($rows as $r) {
    echo "ID:{$r->id} created_by:{$r->created_by} value:{$r->value}\n";
}

// Check landing page logo setting
$lp = \DB::table('landing_page_settings')->where('name', 'logo')->orWhere('name', 'company_logo')->get();
echo "\nLanding page settings:\n";
foreach ($lp as $r) {
    echo "name:{$r->name} value:{$r->value}\n";
}

// List files in logo folders
echo "\nstorage/uploads/logo files:\n";
$dir1 = base_path('storage/uploads/logo');
if (is_dir($dir1)) foreach (scandir($dir1) as $f) { if ($f !== '.' && $f !== '..') echo "  $f\n"; }

echo "\npublic/uploads/logo files:\n";
$dir2 = public_path('uploads/logo');
if (is_dir($dir2)) foreach (scandir($dir2) as $f) { if ($f !== '.' && $f !== '..') echo "  $f\n"; }
