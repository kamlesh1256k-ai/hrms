<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "USERS\n";
foreach(App\Models\User::select('id','name','email','type','created_by')->orderBy('id')->get() as $u){echo "$u->id|$u->name|$u->email|$u->type|cb=$u->created_by\n";}

echo "\nBRANCH\n";
foreach(App\Models\Branch::select('id','name','created_by')->orderBy('id')->get() as $r){echo "$r->id|$r->name|cb=$r->created_by\n";}

echo "\nDEPT\n";
foreach(App\Models\Department::select('id','name','created_by')->orderBy('id')->get() as $r){echo "$r->id|$r->name|cb=$r->created_by\n";}

echo "\nDESG\n";
foreach(App\Models\Designation::select('id','name','created_by')->orderBy('id')->get() as $r){echo "$r->id|$r->name|cb=$r->created_by\n";}

echo "\nSTRUCT\n";
foreach(App\Models\SalaryStructure::select('id','name','created_by')->orderBy('id')->get() as $r){echo "$r->id|$r->name|cb=$r->created_by\n";}
