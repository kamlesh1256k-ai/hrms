<?php
// Seeds default KPI master data (industries, company sizes, etc.)
// Run once: php scripts/seed_kpi_masters.php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

$cid = 6;
$now = Carbon::now();

$sets = [
    'gr_kpi_industries' => [
        'IT & SaaS','Manufacturing','Healthcare','Retail & Hospitality','Logistics & Transportation',
        'Construction','Education','Finance & Banking','E-commerce','Media & Entertainment','Others',
    ],
    'gr_kpi_company_sizes' => [
        '0-10','11-20','21-50','51-100','101-150','151-200','201-500','501-1000','1000+',
    ],
    'gr_kpi_seniority_levels' => ['Entry','Mid','Senior'],
    'gr_kpi_work_models' => ['Onsite','Remote','Hybrid','Shift-Based'],
    'gr_kpi_company_types' => [
        'Private Limited Company','Public Limited Company','Limited Liability Company (LLC)',
        'Corporation (Inc. / Corp.)','Sole Proprietorship (Sole Trader)','Limited Partnership (LP)',
        'Limited Liability Partnership (LLP)','Nonprofit / Charity / NGO','Free Zone Company (FZE / FZCO / FZ-LLC)',
        'GmbH (Germany/Austria private limited)','SARL / Sàrl (France/Lux/Swiss private limited)',
        'SAS (France)','BV (Netherlands/Belgium private limited)','SE (European Company)',
        'Pte Ltd (Singapore private limited)','Co., Ltd (Common in East/SE Asia)',
    ],
    'gr_kpi_timeframes' => ['Quarterly','Half-Yearly','Annual'],
];

echo "=== Seeding KPI Masters for creator_id=$cid ===\n";
foreach ($sets as $table => $names) {
    $exists = DB::table($table)->where('created_by', $cid)->count();
    if ($exists > 0) { echo "$table: already has $exists rows, skipping.\n"; continue; }
    $rows = [];
    foreach ($names as $i => $n) {
        $rows[] = [
            'name' => $n,
            'sort_order' => $i,
            'is_active' => 1,
            'created_by' => $cid,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    DB::table($table)->insert($rows);
    echo "$table: inserted " . count($rows) . "\n";
}
echo "\n✓ Done.\n";
