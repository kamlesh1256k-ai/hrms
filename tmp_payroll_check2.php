<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach ([65,75] as $id) {
  $p = App\Models\Payroll::find($id);
  if (!$p) { echo "ID=$id NOT_FOUND\n"; continue; }
  $att = $p->statutory_json['attendance'] ?? [];
  $paidDays = (float)($att['paid_days'] ?? 0);
  $days = (int)($att['month_total_days'] ?? 30);
  $basicAnnual = 0.0;
  foreach (($p->earnings_json ?? []) as $it) {
    if (stripos((string)($it['name'] ?? ''), 'basic') !== false) { $basicAnnual += (float)($it['amount'] ?? 0); }
  }
  $basicMonthly = round($basicAnnual / 12, 2);
  $basicEarn = $days > 0 ? round(($basicMonthly / $days) * $paidDays, 2) : $basicMonthly;
  echo "ID=$id MONTH={$p->month} basicMonthly={$basicMonthly} basicEarn={$basicEarn} storedEPF=" . (($p->statutory_json['epf_employee'] ?? 'NA')) . "\n";
}
