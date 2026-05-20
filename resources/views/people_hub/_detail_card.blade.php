<div class="row">
    <div class="col-md-12">
        <div class="text-center mb-3">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white" style="width:70px;height:70px;font-size:1.8rem;font-weight:700;">
                {{ strtoupper(substr($emp->name, 0, 1)) }}
            </div>
            <h5 class="mt-2 mb-0">{{ $emp->name }}</h5>
            <small class="text-muted">{{ $emp->designation->name ?? '—' }} · {{ $emp->department->name ?? '—' }}</small>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="p-3" style="background:#f8fafc;border-radius:10px;">
            <h6 class="mb-2" style="font-size:.82rem;color:#6366f1;"><i class="ti ti-id me-1"></i>{{ __('Basic Info') }}</h6>
            <table class="table table-sm mb-0" style="font-size:.82rem;">
                <tr><td class="text-muted" width="40%">{{ __('Employee ID') }}</td><td><strong>{{ $emp->employee_id }}</strong></td></tr>
                <tr><td class="text-muted">{{ __('Email') }}</td><td>{{ $emp->email }}</td></tr>
                <tr><td class="text-muted">{{ __('Phone') }}</td><td>{{ $emp->phone ?? '—' }}</td></tr>
                <tr><td class="text-muted">{{ __('Gender') }}</td><td>{{ $emp->gender }}</td></tr>
                <tr><td class="text-muted">{{ __('DOB') }}</td><td>{{ $emp->dob ? \Carbon\Carbon::parse($emp->dob)->format('d M Y') : '—' }}</td></tr>
                <tr><td class="text-muted">{{ __('DOJ') }}</td><td>{{ $emp->company_doj ?? '—' }}</td></tr>
                <tr><td class="text-muted">{{ __('Blood Group') }}</td><td>{{ $emp->blood_group ?? '—' }}</td></tr>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3" style="background:#f8fafc;border-radius:10px;">
            <h6 class="mb-2" style="font-size:.82rem;color:#dc2626;"><i class="ti ti-urgent me-1"></i>{{ __('Emergency Contact') }}</h6>
            <table class="table table-sm mb-0" style="font-size:.82rem;">
                <tr><td class="text-muted" width="40%">{{ __('Name') }}</td><td>{{ $emp->emergency_contact_name ?? '—' }}</td></tr>
                <tr><td class="text-muted">{{ __('Phone') }}</td><td>{{ $emp->emergency_contact_phone ?? '—' }}</td></tr>
            </table>

            <h6 class="mb-2 mt-3" style="font-size:.82rem;color:#059669;"><i class="ti ti-building me-1"></i>{{ __('Office') }}</h6>
            <table class="table table-sm mb-0" style="font-size:.82rem;">
                <tr><td class="text-muted" width="40%">{{ __('Branch') }}</td><td>{{ $emp->branch->name ?? '—' }}</td></tr>
                <tr><td class="text-muted">{{ __('Department') }}</td><td>{{ $emp->department->name ?? '—' }}</td></tr>
                <tr><td class="text-muted">{{ __('Shift') }}</td><td>{{ ucfirst($emp->shift_type) }}</td></tr>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3" style="background:#f8fafc;border-radius:10px;">
            <h6 class="mb-2" style="font-size:.82rem;color:#0891b2;"><i class="ti ti-shield-check me-1"></i>{{ __('Insurance Details') }}</h6>
            <table class="table table-sm mb-0" style="font-size:.82rem;">
                <tr><td class="text-muted" width="40%">{{ __('Insurance ID') }}</td><td>{{ $emp->insurance_id ?? '—' }}</td></tr>
                <tr><td class="text-muted">{{ __('Insurer Name') }}</td><td>{{ $emp->insurer_name ?? '—' }}</td></tr>
                <tr><td class="text-muted">{{ __('Contact Person') }}</td><td>{{ $emp->insurance_contact_person ?? '—' }}</td></tr>
                <tr><td class="text-muted">{{ __('ESIC Number') }}</td><td>{{ $emp->esic_number ?? '—' }}</td></tr>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3" style="background:#f8fafc;border-radius:10px;">
            <h6 class="mb-2" style="font-size:.82rem;color:#f59e0b;"><i class="ti ti-users me-1"></i>{{ __('Reporting') }}</h6>
            <table class="table table-sm mb-0" style="font-size:.82rem;">
                <tr><td class="text-muted" width="40%">{{ __('Manager') }}</td><td><strong>{{ $emp->reportingManager->name ?? '—' }}</strong></td></tr>
                @if($mentor)<tr><td class="text-muted">{{ __('Mentor/Buddy') }}</td><td><strong>{{ $mentor->name }}</strong></td></tr>@endif
            </table>
        </div>
    </div>
</div>
