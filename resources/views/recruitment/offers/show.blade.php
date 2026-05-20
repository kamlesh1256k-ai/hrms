@extends('layouts.admin')

@section('page-title') {{ __('Offer') }} #{{ $offer->id }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.offers.index') }}">{{ __('Offers') }}</a></li>
    <li class="breadcrumb-item">#{{ $offer->id }}</li>
@endsection

@push('css-page')
<style>
    .of-step {
        display: flex; align-items: center; gap: 10px; padding: 10px 14px;
        border-left: 3px solid #e2e8f0; background: #f8fafc; margin-bottom: 4px;
    }
    .of-step.done { border-left-color: #10b981; background: #ecfdf5; }
    .of-step.active { border-left-color: #4361ee; background: #eef2ff; }
    .of-step.danger { border-left-color: #ef4444; background: #fef2f2; }
    .of-dot {
        width: 22px; height: 22px; border-radius: 50%; background: #cbd5e1; color: #fff;
        display: flex; align-items: center; justify-content: center; font-size: .65rem; font-weight: 700;
        flex-shrink: 0;
    }
    .of-step.done   .of-dot { background: #10b981; }
    .of-step.active .of-dot { background: #4361ee; }
    .of-step.danger .of-dot { background: #ef4444; }
    .of-totals { background:#0f172a; color:#fff; border-radius:10px; padding:18px 22px; }
    .of-totals .lbl { font-size:.72rem; opacity:.7; text-transform:uppercase; letter-spacing:.5px; }
    .of-totals .val { font-size:1.7rem; font-weight:700; line-height:1.1; margin-top:2px; }
    .of-row-input { font-size:.85rem; }
    .of-action-bar { background: #f8fafc; border-radius: 10px; padding: 14px; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif
    @if(session('info'))    <div class="alert alert-info">{{ session('info') }}</div>      @endif

    {{-- Header --}}
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">
                {{ $offer->applications->name ?? '—' }}
                <span class="badge bg-{{ \App\Models\JobOnBoard::$statusBadge[$offer->status] ?? 'secondary' }} ms-2">
                    {{ \App\Models\JobOnBoard::$statuses[$offer->status] ?? ucfirst($offer->status) }}
                </span>
            </h4>
            <div class="text-muted small">
                {{ $offer->applications->email ?? '' }} · {{ $offer->applications->phone ?? '' }}
                @if($offer->applications->jobs)
                    · <strong>{{ $offer->applications->jobs->title }}</strong>
                @endif
            </div>
        </div>
        <a href="{{ route('recruitment.offers.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>{{ __('Back') }}
        </a>
    </div>

    <div class="row">
        {{-- LEFT: compensation breakup --}}
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="ti ti-receipt-2 me-1 text-primary"></i>{{ __('Compensation Breakup') }}</h6>
                </div>
                <form method="POST" action="{{ route('recruitment.offers.compensation', $offer->id) }}" id="comp-form" class="card-body">
                    @csrf
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('Currency') }}</label>
                            <input type="text" name="currency" value="{{ $offer->currency ?? 'INR' }}" class="form-control form-control-sm" maxlength="8">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">{{ __('Offer Expiry Date') }}</label>
                            <input type="date" name="offer_expiry_date" value="{{ $offer->offer_expiry_date?->toDateString() }}" class="form-control form-control-sm">
                        </div>
                    </div>

                    <table class="table table-sm align-middle" id="comp-table">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Component') }}</th>
                                <th width="160px">{{ __('Amount') }}</th>
                                <th width="160px">{{ __('Cadence') }}</th>
                                <th width="40px"></th>
                            </tr>
                        </thead>
                        <tbody id="comp-rows">
                            @foreach(($offer->compensation_breakup ?: []) as $idx => $row)
                                <tr class="comp-row">
                                    <td>
                                        <input type="text" name="rows[{{ $idx }}][label]" value="{{ $row['label'] ?? '' }}" class="form-control of-row-input" required maxlength="120">
                                    </td>
                                    <td>
                                        <input type="number" name="rows[{{ $idx }}][amount]" value="{{ $row['amount'] ?? 0 }}" class="form-control of-row-input comp-amount" min="0" step="0.01" required onchange="recalcCtc()">
                                    </td>
                                    <td>
                                        <select name="rows[{{ $idx }}][cadence]" class="form-select of-row-input comp-cadence" onchange="recalcCtc()">
                                            <option value="monthly" @selected(($row['cadence'] ?? 'monthly') === 'monthly')>{{ __('Monthly') }}</option>
                                            <option value="annual"  @selected(($row['cadence'] ?? '') === 'annual')>{{ __('Annual') }}</option>
                                            <option value="one_time" @selected(($row['cadence'] ?? '') === 'one_time')>{{ __('One-time') }}</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove(); recalcCtc();">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCompRow()">
                            <i class="ti ti-plus me-1"></i>{{ __('Add Component') }}
                        </button>
                        <div class="d-flex gap-3 align-items-center">
                            <span class="text-muted small">{{ __('Annual CTC') }}:
                                <strong id="comp-total" class="text-primary fs-5">{{ number_format($offer->total_ctc ?? 0, 0) }}</strong>
                                <span class="ms-1">{{ $offer->currency ?? 'INR' }}</span>
                            </span>
                            <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('Save') }}</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Negotiation log --}}
            @if($offer->negotiation_notes || in_array($offer->status, ['offer_released','negotiation'], true))
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0"><i class="ti ti-messages me-1 text-warning"></i>{{ __('Negotiation Log') }}</h6></div>
                    <div class="card-body">
                        @if($offer->negotiation_notes)
                            <pre class="bg-light p-3 rounded small" style="white-space:pre-wrap;">{{ $offer->negotiation_notes }}</pre>
                        @endif
                        <form method="POST" action="{{ route('recruitment.offers.negotiation', $offer->id) }}">
                            @csrf
                            <textarea name="negotiation_notes" rows="3" class="form-control" required maxlength="5000" placeholder="{{ __('Candidate is asking for ₹2L more / wants joining bonus instead of stock…') }}"></textarea>
                            <div class="text-end mt-2">
                                <button class="btn btn-sm btn-warning"><i class="ti ti-message-circle-2 me-1"></i>{{ __('Add Negotiation Note') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if($offer->decline_reason)
                <div class="alert alert-danger">
                    <strong><i class="ti ti-x-circle me-1"></i>{{ __('Decline reason') }}:</strong>
                    <span style="white-space:pre-wrap;">{{ $offer->decline_reason }}</span>
                </div>
            @endif
        </div>

        {{-- RIGHT: lifecycle + actions --}}
        <div class="col-lg-4">
            {{-- CTC summary card --}}
            <div class="of-totals mb-3">
                <div class="lbl">{{ __('Total Annual CTC') }}</div>
                <div class="val">
                    {{ $offer->currency ?? 'INR' }}
                    <span id="comp-total-pretty">{{ number_format($offer->total_ctc ?? 0, 0) }}</span>
                </div>
                @if((float) $offer->total_ctc >= $threshold)
                    <small class="text-warning d-block mt-1">
                        <i class="ti ti-alert-triangle"></i>
                        {{ __('Above approval threshold of') }} {{ number_format($threshold, 0) }}
                    </small>
                @endif
            </div>

            {{-- Lifecycle steps --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-route me-1"></i>{{ __('Lifecycle') }}</h6></div>
                <div class="card-body p-2">
                    @php
                        $flow = ['pending','awaiting_approval','offer_released','negotiation','accepted'];
                        $idx  = array_search($offer->status, $flow, true);
                        $idx  = $idx === false ? 0 : $idx;
                        $isDeclined = in_array($offer->status, ['declined','cancel'], true);
                    @endphp
                    @foreach (['pending'=>'Draft','awaiting_approval'=>'Awaiting Approval','offer_released'=>'Released','negotiation'=>'Negotiation','accepted'=>'Accepted'] as $key => $label)
                        @php
                            $pos = array_search($key, $flow, true);
                            $cls = '';
                            if (!$isDeclined && $pos < $idx) $cls = 'done';
                            elseif (!$isDeclined && $pos === $idx) $cls = 'active';
                            elseif ($isDeclined && $offer->status === 'declined' && $key === 'offer_released') $cls = 'done';
                        @endphp
                        <div class="of-step {{ $cls }}">
                            <span class="of-dot">{{ $loop->iteration }}</span>
                            <span class="small">{{ $label }}</span>
                            <span class="ms-auto small text-muted">
                                @if($key === 'offer_released' && $offer->offer_released_at) {{ $offer->offer_released_at->format('d M, H:i') }} @endif
                                @if($key === 'awaiting_approval' && $offer->approved_at) {{ __('approved') }} @endif
                                @if($key === 'accepted' && $offer->accepted_declined_at && $offer->status === 'accepted') {{ $offer->accepted_declined_at->format('d M') }} @endif
                            </span>
                        </div>
                    @endforeach
                    @if($isDeclined)
                        <div class="of-step danger">
                            <span class="of-dot"><i class="ti ti-x"></i></span>
                            <span class="small">{{ __('Declined') }}</span>
                            <span class="ms-auto small text-muted">{{ $offer->accepted_declined_at?->format('d M, H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="of-action-bar">
                <h6 class="small fw-bold mb-2">{{ __('Actions') }}</h6>

                {{-- Release / Approve --}}
                @if(in_array($offer->status, ['pending'], true))
                    <form method="POST" action="{{ route('recruitment.offers.release', $offer->id) }}" class="mb-2">
                        @csrf
                        <button class="btn btn-info w-100" {{ $offer->total_ctc ? '' : 'disabled' }}>
                            <i class="ti ti-send me-1"></i>{{ __('Release Offer') }}
                        </button>
                        @if(!$offer->total_ctc)
                            <small class="text-muted d-block mt-1">{{ __('Save the compensation breakup first.') }}</small>
                        @endif
                    </form>
                @endif

                @if($offer->status === 'awaiting_approval' && $isApprover)
                    <form method="POST" action="{{ route('recruitment.offers.approve', $offer->id) }}" class="mb-2">
                        @csrf
                        <button class="btn btn-success w-100"><i class="ti ti-check me-1"></i>{{ __('Approve & Release') }}</button>
                    </form>
                @elseif($offer->status === 'awaiting_approval')
                    <div class="alert alert-warning small mb-2">
                        <i class="ti ti-clock me-1"></i>{{ __('Awaiting approval from HR / Company admin.') }}
                    </div>
                @endif

                @if(in_array($offer->status, ['offer_released','negotiation'], true))
                    <form method="POST" action="{{ route('recruitment.offers.accept', $offer->id) }}" class="mb-2">
                        @csrf
                        <button class="btn btn-success w-100"
                                onclick="return confirm('{{ __('Mark candidate as accepted?') }}')">
                            <i class="ti ti-circle-check me-1"></i>{{ __('Mark Accepted') }}
                        </button>
                    </form>
                    <button class="btn btn-outline-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#declineModal">
                        <i class="ti ti-x me-1"></i>{{ __('Mark Declined') }}
                    </button>
                @endif

                {{-- Joining / Employee Info --}}
                @if($isApprover)
                    @if(!empty($offer->convert_to_employee) && \Route::has('employee.show'))
                        <a href="{{ route('employee.show', \Crypt::encrypt($offer->convert_to_employee)) }}" class="btn btn-primary w-100 mb-2">
                            <i class="ti ti-user-plus me-1"></i>{{ __('Open Employee Profile') }}
                        </a>
                    @elseif(in_array($offer->status, ['accepted','confirm'], true) && \Route::has('job.on.board.convert'))
                        <a href="{{ route('job.on.board.convert', $offer->id) }}" class="btn btn-primary w-100 mb-2">
                            <i class="ti ti-user-plus me-1"></i>{{ __('Add Employee Information') }}
                        </a>
                        <small class="text-muted d-block mb-2">{{ __('After creating the employee, the offer will be marked as Joined.') }}</small>
                    @endif
                @endif

                {{-- Offer letter upload --}}
                <hr>
                <form method="POST" action="{{ route('recruitment.offers.letter', $offer->id) }}" enctype="multipart/form-data">
                    @csrf
                    <label class="form-label small fw-bold">{{ __('Offer Letter (PDF/DOC)') }}</label>
                    <input type="file" name="offer_letter" class="form-control form-control-sm" accept=".pdf,.doc,.docx" required>
                    <button class="btn btn-outline-primary btn-sm w-100 mt-2">
                        <i class="ti ti-upload me-1"></i>{{ __('Upload Letter') }}
                    </button>
                    @if($offer->offer_letter_path)
                        <a href="{{ asset('storage/'.$offer->offer_letter_path) }}" target="_blank" class="btn btn-sm btn-link p-0 mt-1">
                            <i class="ti ti-paperclip"></i> {{ basename($offer->offer_letter_path) }}
                        </a>
                    @endif
                </form>

                {{-- Auto-generate offer letter via existing JobOnBoard PDF route --}}
                @if(\Route::has('offerlatter.download.pdf'))
                    <hr>
                    <a href="{{ route('offerlatter.download.pdf', $offer->id) }}" class="btn btn-outline-secondary btn-sm w-100" target="_blank">
                        <i class="ti ti-file-type-pdf me-1"></i>{{ __('Auto-generate Offer PDF') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Decline modal --}}
<div class="modal fade" id="declineModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('recruitment.offers.decline', $offer->id) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Mark Offer as Declined') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">{{ __('Reason for declining') }} <span class="text-danger">*</span></label>
                <textarea name="decline_reason" rows="4" class="form-control" required maxlength="2000"
                          placeholder="{{ __('Counter-offer not matched / accepted another role / location issue…') }}"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-danger"><i class="ti ti-x me-1"></i>{{ __('Confirm Decline') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function addCompRow() {
    var tbody = document.getElementById('comp-rows');
    var idx   = tbody.querySelectorAll('tr').length;
    var tr    = document.createElement('tr');
    tr.className = 'comp-row';
    tr.innerHTML = `
        <td><input type="text" name="rows[${idx}][label]" class="form-control of-row-input" required maxlength="120" placeholder="{{ __('Component name') }}"></td>
        <td><input type="number" name="rows[${idx}][amount]" value="0" class="form-control of-row-input comp-amount" min="0" step="0.01" required onchange="recalcCtc()"></td>
        <td><select name="rows[${idx}][cadence]" class="form-select of-row-input comp-cadence" onchange="recalcCtc()">
            <option value="monthly">{{ __('Monthly') }}</option>
            <option value="annual">{{ __('Annual') }}</option>
            <option value="one_time">{{ __('One-time') }}</option>
        </select></td>
        <td><button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove(); recalcCtc();"><i class="ti ti-trash"></i></button></td>`;
    tbody.appendChild(tr);
}

function recalcCtc() {
    var rows = document.querySelectorAll('.comp-row');
    var total = 0;
    rows.forEach(function (r) {
        var amt = parseFloat(r.querySelector('.comp-amount').value) || 0;
        var cad = r.querySelector('.comp-cadence').value;
        if (cad === 'monthly')      total += amt * 12;
        else                        total += amt;
    });
    var fmt = total.toLocaleString();
    document.getElementById('comp-total').textContent = fmt;
    var pretty = document.getElementById('comp-total-pretty');
    if (pretty) pretty.textContent = fmt;
}
</script>
@endsection
