@extends('layouts.admin')
@section('page-title', __('Salary Statement') . ($monthLabel ? ' - ' . $monthLabel : ''))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('payroll.process') }}">{{ __('Payroll') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Salary Statement PDF') }}</li>
@endsection

@push('css-page')
<style>
    .stmt-wrap { max-width: 100%; margin: 0 auto; }
    .stmt { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
    .stmt-hdr { background: #1e293b; color: #fff; padding: 16px 20px; }
    .stmt-hdr h3 { margin: 0; font-size: 1.1rem; font-weight: 700; }
    .stmt-hdr .sub { font-size: .78rem; opacity: .7; }
    .stmt-tbl { width: 100%; border-collapse: collapse; font-size: .68rem; }
    .stmt-tbl th { padding: 5px 6px; font-weight: 600; text-align: center; white-space: nowrap; border: 1px solid #d1d5db; }
    .stmt-tbl td { padding: 4px 6px; border: 1px solid #e5e7eb; text-align: right; white-space: nowrap; }
    .stmt-tbl td:first-child, .stmt-tbl td:nth-child(2), .stmt-tbl td:nth-child(3), .stmt-tbl td:nth-child(4), .stmt-tbl td:nth-child(5), .stmt-tbl td:nth-child(6) { text-align: left; }
    .stmt-tbl .hdr-info th { background: #1e293b; color: #fff; }
    .stmt-tbl .hdr-attn th { background: #475569; color: #fff; }
    .stmt-tbl .hdr-earn th { background: #166534; color: #fff; }
    .stmt-tbl .hdr-ded th { background: #991b1b; color: #fff; }
    .stmt-tbl .hdr-net th { background: #1e40af; color: #fff; font-size: .75rem; }
    .stmt-tbl .hdr-emp th { background: #1e3a5f; color: #fff; }
    .stmt-tbl .tot-col { font-weight: 700; background: #f0f4ff; }
    .stmt-tbl .net-col { font-weight: 800; background: #dbeafe; }
    .stmt-tbl tr:nth-child(even) td { background: #fafbfc; }
    .stmt-tbl tr:hover td { background: #f1f5f9; }
    @media print {
        .no-print { display: none !important; }
        .dash-sidebar, .dash-header, .dash-footer, .page-header, .breadcrumb { display: none !important; }
        .dash-container, .dash-content { margin: 0 !important; padding: 0 !important; }
        .stmt-wrap { max-width: 100%; }
        body { background: #fff !important; font-size: 9px !important; }
        .stmt-tbl { font-size: .6rem; }
        .stmt-tbl th, .stmt-tbl td { padding: 2px 3px; }
    }
</style>
@endpush

@section('content')
<div class="stmt-wrap">
    <div class="mb-3 no-print d-flex gap-2">
        <button onclick="downloadPDF()" class="btn btn-sm btn-danger"><i class="ti ti-file-type-pdf me-1"></i>{{ __('Download PDF') }}</button>
        <button onclick="window.print()" class="btn btn-sm btn-primary"><i class="ti ti-printer me-1"></i>{{ __('Print') }}</button>
        <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}</a>
    </div>

    <div class="stmt" id="salary-statement">
        <div class="stmt-hdr">
            <h3>{{ $companyName }} — {{ __('Salary Statement') }}</h3>
            @if($monthLabel)
                <div class="sub">{{ $monthLabel }}</div>
            @endif
            <div class="sub">{{ __('Generated:') }} {{ now()->format('d M Y, h:i A') }}</div>
        </div>

        <div style="overflow-x:auto;padding:0;">
            <table class="stmt-tbl">
                {{-- ═══ HEADER ROW 1: Section Groups ═══ --}}
                <thead>
                @php
                    // Discover all components across payrolls
                    $allEarnings = [];
                    $allDeductions = [];
                    $allBenefits = [];
                    $normalizeEarning = function($n) {
                        $l = strtolower($n);
                        if ($l === 'house rent allowance') return 'HRA';
                        if ($l === 'conveyance') return 'Conveyance Allowance';
                        if ($l === 'medical') return 'Medical Allowance';
                        if ($l === 'overtime allowance') return 'Overtime';
                        return $n;
                    };
                    $normalizeDeduction = function($n) {
                        $l = strtolower($n);
                        if (str_contains($l, 'absent deduction')) return 'Absent Ded';
                        if (str_contains($l, 'half day deduction')) return 'HD Ded';
                        if (str_contains($l, 'epf') || str_contains($l, 'pf employee')) return 'EPF';
                        if (str_contains($l, 'esic') && str_contains($l, 'employee')) return 'ESIC';
                        if (str_contains($l, 'professional tax')) return 'PT';
                        if (str_contains($l, 'lwf') && str_contains($l, 'employee')) return 'LWF';
                        return $n;
                    };
                    $normalizeBenefit = function($n) {
                        $l = strtolower($n);
                        if (str_contains($l, 'epf') || str_contains($l, 'pf') && str_contains($l, 'employer')) return 'EPF Employer';
                        if (str_contains($l, 'esic') && str_contains($l, 'employer')) return 'ESIC Employer';
                        if (str_contains($l, 'lwf') && str_contains($l, 'employer')) return 'LWF Employer';
                        if (str_contains($l, 'gratuity')) return 'Gratuity';
                        return $n;
                    };

                    foreach ($payrolls as $p) {
                        foreach (($p->earnings_json ?? []) as $i) {
                            $n = $normalizeEarning(trim($i['name'] ?? ''));
                            if ($n && !in_array($n, $allEarnings)) $allEarnings[] = $n;
                        }
                        foreach (($p->deductions_json ?? []) as $i) {
                            $n = $normalizeDeduction(trim($i['name'] ?? ''));
                            if ($n && !stripos($n, 'employer') && !in_array($n, $allDeductions)) $allDeductions[] = $n;
                        }
                        foreach (($p->benefits_json ?? []) as $i) {
                            $n = $normalizeBenefit(trim($i['name'] ?? ''));
                            if ($n && !in_array($n, $allBenefits)) $allBenefits[] = $n;
                        }
                        if (($p->statutory_json['gratuity'] ?? 0) > 0 && !in_array('Gratuity', $allBenefits)) {
                            $allBenefits[] = 'Gratuity';
                        }
                    }

                    // Sort
                    $earnOrder = ['Basic','HRA','Conveyance Allowance','Medical Allowance','Special Allowance','Fixed Allowance','Overtime','Salary Arrears'];
                    $sortedEarnings = [];
                    foreach ($earnOrder as $o) { if (in_array($o, $allEarnings)) $sortedEarnings[] = $o; }
                    foreach ($allEarnings as $n) { if (!in_array($n, $sortedEarnings)) $sortedEarnings[] = $n; }
                    $allEarnings = $sortedEarnings;

                    $dedOrder = ['PT','EPF','ESIC','LWF','Absent Ded','HD Ded'];
                    $sortedDed = [];
                    foreach ($dedOrder as $o) { if (in_array($o, $allDeductions)) $sortedDed[] = $o; }
                    foreach ($allDeductions as $n) { if (!in_array($n, $sortedDed)) $sortedDed[] = $n; }
                    $allDeductions = $sortedDed;

                    $benOrder = ['EPF Employer','ESIC Employer','Gratuity','LWF Employer'];
                    $sortedBen = [];
                    foreach ($benOrder as $o) { if (in_array($o, $allBenefits)) $sortedBen[] = $o; }
                    foreach ($allBenefits as $n) { if (!in_array($n, $sortedBen)) $sortedBen[] = $n; }
                    $allBenefits = $sortedBen;

                    $earnColCount = count($allEarnings) * 2 + 2; // Monthly+Earned per + 2 totals
                    $dedColCount = count($allDeductions) + 1; // + total
                    $benColCount = count($allBenefits) + 1; // + total
                @endphp

                {{-- Section group row --}}
                <tr>
                    <th colspan="6" class="hdr-info">{{ __('Employee') }}</th>
                    <th colspan="5" class="hdr-attn">{{ __('Attendance') }}</th>
                    <th class="hdr-info">{{ __('CTC') }}</th>
                    <th colspan="{{ $earnColCount }}" class="hdr-earn">{{ __('Earnings') }}</th>
                    <th colspan="{{ $dedColCount }}" class="hdr-ded">{{ __('Deductions') }}</th>
                    <th class="hdr-net">{{ __('Net') }}</th>
                    <th colspan="{{ $benColCount }}" class="hdr-emp">{{ __('Employer') }}</th>
                </tr>

                {{-- Column names row --}}
                <tr>
                    <th class="hdr-info">#</th>
                    <th class="hdr-info">{{ __('Code') }}</th>
                    <th class="hdr-info">{{ __('Name') }}</th>
                    <th class="hdr-info">{{ __('Dept') }}</th>
                    <th class="hdr-info">{{ __('Desig.') }}</th>
                    <th class="hdr-info">{{ __('Month') }}</th>

                    <th class="hdr-attn">{{ __('Days') }}</th>
                    <th class="hdr-attn">{{ __('Paid') }}</th>
                    <th class="hdr-attn">{{ __('P') }}</th>
                    <th class="hdr-attn">{{ __('L') }}</th>
                    <th class="hdr-attn">{{ __('A/HD') }}</th>

                    <th class="hdr-info">{{ __('Annual') }}</th>

                    @foreach($allEarnings as $en)
                        <th class="hdr-earn" style="font-size:.6rem;">{{ $en }}<br><small>M</small></th>
                        <th class="hdr-earn" style="font-size:.6rem;">{{ $en }}<br><small>E</small></th>
                    @endforeach
                    <th class="hdr-earn" style="font-size:.65rem;">{{ __('Gross') }}<br><small>M</small></th>
                    <th class="hdr-earn" style="font-size:.65rem;">{{ __('Gross') }}<br><small>E</small></th>

                    @foreach($allDeductions as $dn)
                        <th class="hdr-ded" style="font-size:.6rem;">{{ $dn }}</th>
                    @endforeach
                    <th class="hdr-ded" style="font-size:.65rem;">{{ __('Total') }}</th>

                    <th class="hdr-net">{{ __('NET PAY') }}</th>

                    @foreach($allBenefits as $bn)
                        <th class="hdr-emp" style="font-size:.6rem;">{{ $bn }}</th>
                    @endforeach
                    <th class="hdr-emp" style="font-size:.65rem;">{{ __('Total') }}</th>
                </tr>
                </thead>

                <tbody>
                @foreach($payrolls as $idx => $p)
                    @php
                        $attn = $p->statutory_json['attendance'] ?? [];
                        $calDays = (int)($attn['month_calendar_days'] ?? date('t', strtotime($p->month.'-01')));
                        $paidDays = (float)($attn['paid_days'] ?? $calDays);
                        $emp = $p->employee;
                        $empSal = \App\Models\EmployeeSalary::where('employee_id', $p->employee_id)->first();

                        // Build earnings map
                        $eMap = [];
                        foreach (($p->earnings_json ?? []) as $i) {
                            $n = $normalizeEarning(trim($i['name'] ?? ''));
                            if (!$n) continue;
                            $ann = (float)($i['amount'] ?? 0);
                            $oneTime = (($i['frequency'] ?? 'monthly') === 'one-time');
                            $m = $oneTime ? $ann : round($ann / 12, 2);
                            $e = $oneTime ? $m : ($calDays > 0 ? round(($m / $calDays) * $paidDays, 2) : $m);
                            if (!isset($eMap[$n])) $eMap[$n] = ['m' => 0, 'e' => 0];
                            $eMap[$n]['m'] += $m;
                            $eMap[$n]['e'] += $e;
                        }

                        // Build deductions map
                        $dMap = [];
                        foreach (($p->deductions_json ?? []) as $i) {
                            $n = $normalizeDeduction(trim($i['name'] ?? ''));
                            if (!$n || stripos($n, 'employer') !== false) continue;
                            $dMap[$n] = ($dMap[$n] ?? 0) + (float)($i['amount'] ?? 0);
                        }

                        // Build benefits map
                        $bMap = [];
                        foreach (($p->benefits_json ?? []) as $i) {
                            $n = $normalizeBenefit(trim($i['name'] ?? ''));
                            if ($n) $bMap[$n] = ($bMap[$n] ?? 0) + (float)($i['amount'] ?? 0);
                        }
                        $sj = $p->statutory_json ?? [];
                        if (empty($bMap['Gratuity']) && ($sj['gratuity'] ?? 0) > 0) $bMap['Gratuity'] = (float)$sj['gratuity'];
                        if (empty($bMap['EPF Employer']) && ($sj['epf_employer'] ?? 0) > 0) $bMap['EPF Employer'] = (float)$sj['epf_employer'];

                        $fmt = function($v) { return number_format(round($v, 0), 0); };
                        $fmt2 = function($v) { return number_format(round($v, 2), 2); };
                    @endphp
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td>{{ $emp->employee_id ?? $p->employee_id }}</td>
                        <td>{{ $emp->name ?? '' }}</td>
                        <td>{{ $emp->department->name ?? '-' }}</td>
                        <td>{{ $emp->designation->name ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($p->month.'-01')->format('M Y') }}</td>

                        <td style="text-align:center;">{{ (int)($attn['month_total_days'] ?? 0) }}</td>
                        <td style="text-align:center;">{{ round($paidDays, 1) }}</td>
                        <td style="text-align:center;">{{ round((float)($attn['present_effective'] ?? 0), 1) }}</td>
                        <td style="text-align:center;">{{ round((float)($attn['leave_effective'] ?? 0), 1) }}</td>
                        <td style="text-align:center;">{{ round((float)($attn['absent_effective'] ?? 0) + (float)($attn['hd_deduction'] ?? 0), 1) }}</td>

                        <td>{{ $empSal ? $fmt($empSal->ctc) : '-' }}</td>

                        @php $totM = 0; $totE = 0; @endphp
                        @foreach($allEarnings as $en)
                            @php
                                $m = round($eMap[$en]['m'] ?? 0, 2); $e = round($eMap[$en]['e'] ?? 0, 2);
                                $totM += $m; $totE += $e;
                            @endphp
                            <td>{{ $m > 0 ? $fmt2($m) : '-' }}</td>
                            <td>{{ $e > 0 ? $fmt2($e) : '-' }}</td>
                        @endforeach
                        <td class="tot-col">{{ $fmt2($totM) }}</td>
                        <td class="tot-col">{{ $fmt2($totE) }}</td>

                        @php $totD = 0; @endphp
                        @foreach($allDeductions as $dn)
                            @php $d = round($dMap[$dn] ?? 0, 2); $totD += $d; @endphp
                            <td>{{ $d > 0 ? $fmt2($d) : '-' }}</td>
                        @endforeach
                        <td class="tot-col">{{ $fmt2($totD) }}</td>

                        <td class="net-col">{{ $fmt2($p->net_salary) }}</td>

                        @php $totB = 0; @endphp
                        @foreach($allBenefits as $bn)
                            @php $b = round($bMap[$bn] ?? 0, 2); $totB += $b; @endphp
                            <td>{{ $b > 0 ? $fmt2($b) : '-' }}</td>
                        @endforeach
                        <td class="tot-col">{{ $fmt2($totB) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>
<script>
function downloadPDF() {
    var el = document.getElementById('salary-statement');
    var monthLabel = '{{ $monthLabel ?: "All" }}';
    html2pdf().set({
        margin: [4, 2, 4, 2],
        filename: 'Salary_Statement_' + monthLabel.replace(/ /g, '_') + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, scrollX: 0, scrollY: 0, width: el.scrollWidth },
        jsPDF: { unit: 'mm', format: 'a3', orientation: 'landscape' }
    }).from(el).save();
}
</script>
@endpush
