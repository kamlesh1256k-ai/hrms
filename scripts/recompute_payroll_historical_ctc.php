<?php
// Recomputes each existing payroll row against the CTC that was actually
// active in that month (from salary_increment_history) and overwrites the
// stored earnings / gross / net. Existing locked rows are overwritten.
//
// Run: php scripts/recompute_payroll_historical_ctc.php [employee_id]
// Omit employee_id to recompute for every employee of creator 6.

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Payroll;
use App\Models\SalaryIncrementHistory;
use App\Services\SalaryCalculator;

$creatorId = 6;
$onlyEmp = (int) ($argv[1] ?? 0);

/** Same logic as PayrollModuleController::ctcEffectiveForMonth */
function ctcForMonth(int $empId, string $month): ?float {
    $monthEnd = Carbon\Carbon::parse($month . '-01')->endOfMonth()->toDateString();
    $latest = SalaryIncrementHistory::where('employee_id', $empId)
        ->where('effective_date', '<=', $monthEnd)
        ->orderByDesc('effective_date')->first();
    if ($latest) return (float) $latest->new_ctc;
    $first = SalaryIncrementHistory::where('employee_id', $empId)
        ->orderBy('effective_date')->first();
    return $first ? (float) $first->old_ctc : null;
}

$calc = app(SalaryCalculator::class);

$query = Payroll::where('created_by', $creatorId);
if ($onlyEmp > 0) $query->where('employee_id', $onlyEmp);

$rows = $query->orderBy('employee_id')->orderBy('month')->get();
echo "Found " . $rows->count() . " payroll rows to recompute.\n";

$updated = 0; $skipped = 0;
foreach ($rows as $row) {
    $empId = (int) $row->employee_id;
    $month = $row->month;
    $ctc = ctcForMonth($empId, $month);
    if ($ctc === null || $ctc <= 0) {
        $skipped++;
        continue;
    }

    $result = $calc->calculate($empId, $month, $ctc);
    if (isset($result['error'])) {
        echo "  emp=$empId $month: ERROR " . $result['error'] . "\n";
        $skipped++;
        continue;
    }

    // Use the earnings/deductions/benefits as stored by runPayroll:
    // earnings_json is the monthly component list (prorated already by runPayroll's
    // attendance logic). Since we don't have attendance context here, we scale the
    // annual values to monthly directly — i.e. "full month" recompute, assuming
    // the employee was paid their full entitlement. Partial-month proration would
    // need attendance, which the UI run already handles; this script is a
    // back-fill to correct the CTC baseline only.
    $earningsMonthly = [];
    foreach (($result['earnings'] ?? []) as $e) {
        $annual = (float) ($e['amount'] ?? 0);
        $freq   = $e['frequency'] ?? 'monthly';
        $earningsMonthly[] = [
            'name'      => $e['name'] ?? 'Component',
            'amount'    => $freq === 'one-time' ? $annual : round($annual / 12, 2),
            'frequency' => $freq,
        ];
    }
    $grossMonthly = round(($result['totals']['gross_annual'] ?? 0) / 12, 2);

    // Keep existing deductions/benefits/statutory as they include attendance
    // and TDS adjustments the UI pipeline calculated. Only overwrite earnings
    // + gross (the columns driven directly by CTC).
    $row->earnings_json = $earningsMonthly;
    $row->gross_salary  = $grossMonthly;
    $row->save();
    $updated++;

    echo sprintf("  emp=%d %s: CTC %s → gross/month %s\n",
        $empId, $month, number_format($ctc), number_format($grossMonthly));
}

echo "\n✓ Recomputed: $updated, skipped: $skipped\n";
