<div class="col-form-label">
    {{-- Employee & Period Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 p-3" style="background:#f8fafc;border-radius:10px;">
        <div>
            <strong style="font-size:1rem;">{{ $employee->name ?? '—' }}</strong>
            <br><small class="text-muted">{{ $employee->employee_id ?? '' }} · {{ $employee->designation->name ?? '' }}</small>
        </div>
        <div class="text-end">
            <span class="badge bg-{{ $status == 'Approved' ? 'success' : ($status == 'Reject' ? 'danger' : 'warning') }}" style="font-size:.8rem;">{{ $status }}</span>
            <br><small class="text-muted">{{ $periodLabel ?? '' }}</small>
        </div>
    </div>

    {{-- Leave Balance Summary Table --}}
    <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered" style="font-size:.82rem;">
            <thead style="background:#eef2ff;">
                <tr>
                    <th>{{ __('Leave Type') }}</th>
                    <th class="text-center">{{ __('Annual Quota') }}</th>
                    <th class="text-center">{{ __('Opening Balance') }}</th>
                    <th class="text-center">{{ __('Availed') }}</th>
                    <th class="text-center">{{ __('Remaining') }}</th>
                    <th class="text-center">{{ __('Carry Forward') }}</th>
                    <th class="text-center">{{ __('Lapsed') }}</th>
                    <th class="text-center">{{ __('Leave Encashment') }}</th>
                </tr>
            </thead>
            <tbody>
                @php $totalQuota = 0; $totalOpening = 0; $totalAvailed = 0; $totalClosing = 0; @endphp
                @foreach ($leaves as $leave)
                @php
                    $totalQuota += $leave->annual_quota;
                    $totalOpening += $leave->opening_balance;
                    $totalAvailed += $leave->taken_days;
                    $totalRemaining = ($totalRemaining ?? 0) + $leave->remaining;
                    $totalCF = ($totalCF ?? 0) + $leave->carry_forward;
                    $totalLapsed = ($totalLapsed ?? 0) + ($leave->lapsed ?? 0);
                    $totalEncashDays = ($totalEncashDays ?? 0) + $leave->encashable_days;
                    $totalEncashAmt = ($totalEncashAmt ?? 0) + $leave->encash_amount;
                @endphp
                <tr>
                    <td><strong>{{ $leave->title }}</strong></td>
                    <td class="text-center">{{ $leave->annual_quota }}</td>
                    <td class="text-center">{{ $leave->opening_balance }}</td>
                    <td class="text-center"><span class="badge bg-{{ $leave->taken_days > 0 ? 'warning' : 'light' }} text-dark">{{ $leave->taken_days }}</span></td>
                    <td class="text-center"><strong>{{ $leave->remaining }}</strong></td>
                    <td class="text-center">
                        @if($leave->carry_forward > 0)
                            <span class="badge bg-primary">{{ $leave->carry_forward }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if(($leave->lapsed ?? 0) > 0)
                            <span class="text-danger">{{ $leave->lapsed }}</span>
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($leave->encashable_days > 0)
                            <span class="badge bg-success">{{ $leave->encashable_days }} {{ __('days') }}</span>
                            <br><small class="text-success" style="font-size:.7rem;">&#8377;{{ number_format($leave->encash_amount) }}</small>
                            <br><small class="text-muted" style="font-size:.6rem;">({{ ucfirst($leave->encash_basis) }}/12/26)</small>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background:#f8fafc;font-weight:700;">
                <tr>
                    <td>{{ __('Total') }}</td>
                    <td class="text-center">{{ $totalQuota }}</td>
                    <td class="text-center">{{ $totalOpening }}</td>
                    <td class="text-center">{{ $totalAvailed }}</td>
                    <td class="text-center">{{ $totalRemaining ?? 0 }}</td>
                    <td class="text-center">{{ $totalCF ?? 0 }}</td>
                    <td class="text-center text-danger">{{ $totalLapsed ?? 0 }}</td>
                    <td class="text-center">{{ $totalEncashDays ?? 0 }} {{ __('days') }}<br><small style="font-size:.7rem;">&#8377;{{ number_format($totalEncashAmt ?? 0) }}</small></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Leave Detail Table --}}
    <h6 class="mb-2" style="font-size:.85rem;"><i class="ti ti-list me-1"></i>{{ __('Leave Details') }} ({{ $status }})</h6>
    <div class="table-responsive">
        <table class="table table-sm" style="font-size:.82rem;">
            <thead>
                <tr>
                    <th>{{ __('Leave Type') }}</th>
                    <th>{{ __('Start Date') }}</th>
                    <th>{{ __('End Date') }}</th>
                    <th class="text-center">{{ __('Days') }}</th>
                    <th>{{ __('Reason') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaveData as $leave)
                    <tr>
                        <td><span class="badge bg-info">{{ !empty($leave->leaveType) ? $leave->leaveType->title : '—' }}</span></td>
                        <td>{{ \Carbon\Carbon::parse($leave->start_date)->format('d M Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}</td>
                        <td class="text-center"><strong>{{ $leave->total_leave_days ?: '—' }}</strong></td>
                        <td>{{ $leave->leave_reason ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">{{ __('No leave records found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
