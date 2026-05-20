<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$p = App\Models\Payroll::find(114);
if(!$p){ echo "NOT_FOUND\n"; exit; }
$att = $p->statutory_json['attendance'] ?? [];
$monthDays = (int)($att['month_total_days'] ?? 30);
$paidDays = (float)($att['paid_days'] ?? $monthDays);
$totalPaid = 0.0; $totalMonthly = 0.0;
foreach(($p->earnings_json ?? []) as $it){
  $ann=(float)($it['amount']??0);
  $one=(($it['frequency']??'monthly')==='one-time');
  $mon=$one?$ann:($ann/12);
  $earn=$one?$mon:($monthDays>0?(($mon/$monthDays)*$paidDays):$mon);
  $totalMonthly += $mon; $totalPaid += $earn;
}
echo 'gross_salary='.$p->gross_salary.PHP_EOL;
echo 'monthDays='.$monthDays.' paidDays='.$paidDays.PHP_EOL;
echo 'totalMonthly='.$totalMonthly.' totalPaid='.$totalPaid.PHP_EOL;
echo 'deductions='.json_encode($p->deductions_json).PHP_EOL;
echo 'benefits='.json_encode($p->benefits_json).PHP_EOL;
echo 'stat='.json_encode($p->statutory_json).PHP_EOL;
