@extends('layouts.admin')
@section('page-title') {{ __('Survey Alerts') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('surveys.index') }}">{{ __('Surveys') }}</a></li>
    <li class="breadcrumb-item">{{ __('Alerts') }}</li>
@endsection

@push('css-page')
<style>
    .al-table th{font-size:.7rem;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:600;background:#fafafa;}
    .pill{font-weight:700;padding:3px 10px;border-radius:20px;font-size:.72rem;}
    .pill-open{background:#fee2e2;color:#991b1b;}
    .pill-res{background:#dcfce7;color:#166534;}
    .risk-high{background:#fee2e2;color:#991b1b;}
    .risk-med {background:#fef3c7;color:#b45309;}
    .risk-low {background:#dbeafe;color:#1d4ed8;}
</style>
@endpush

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">{{ __('Status') }}</label>
                    <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="open" {{ ($status ?? 'open') === 'open' ? 'selected' : '' }}>{{ __('Open') }}</option>
                        <option value="resolved" {{ ($status ?? 'open') === 'resolved' ? 'selected' : '' }}>{{ __('Resolved') }}</option>
                        <option value="all" {{ ($status ?? 'open') === 'all' ? 'selected' : '' }}>{{ __('All') }}</option>
                    </select>
                </div>
                <div class="col-md-9 text-md-end">
                    <a href="{{ route('surveys.pulse') }}" class="btn btn-light btn-sm border">
                        <i class="ti ti-chart-line me-1"></i>{{ __('Pulse Trends') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="ti ti-bell-ringing me-2"></i>{{ __('Survey Alerts') }}</h5>
        </div>
        <div class="card-body p-0">
            @if($alerts->isEmpty())
                <div class="text-center py-5 text-muted">{{ __('No alerts found.') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table al-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">{{ __('Survey') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Risk') }}</th>
                                <th>{{ __('Details') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="pe-3 text-end">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alerts as $a)
                                @php
                                    $msg = [];
                                    if (!empty($a->message)) {
                                        $decoded = json_decode($a->message, true);
                                        if (is_array($decoded)) $msg = $decoded;
                                    }
                                    $riskCls = ($a->risk_level === 'high') ? 'risk-high' : (($a->risk_level === 'medium') ? 'risk-med' : 'risk-low');
                                    $stCls = ($a->status === 'resolved') ? 'pill-res' : 'pill-open';
                                @endphp
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-semibold">{{ $a->survey->title ?? __('(Deleted survey)') }}</div>
                                        <small class="text-muted">{{ $a->created_at?->format('d M Y, h:i A') }}</small>
                                    </td>
                                    <td><span class="text-muted">{{ $a->alert_type }}</span></td>
                                    <td><span class="pill {{ $riskCls }}">{{ ucfirst($a->risk_level) }}</span></td>
                                    <td>
                                        @if(!empty($msg['question_text']))
                                            <div class="fw-semibold">{{ $msg['question_text'] }}</div>
                                            @if(isset($msg['avg']) && isset($msg['total']))
                                                <small class="text-muted">{{ __('Avg :avg (n=:n)', ['avg' => number_format((float)$msg['avg'], 2), 'n' => (int)$msg['total']]) }}</small>
                                            @endif
                                        @else
                                            <small class="text-muted">{{ \Illuminate\Support\Str::limit((string)$a->message, 120) }}</small>
                                        @endif
                                    </td>
                                    <td><span class="pill {{ $stCls }}">{{ ucfirst($a->status) }}</span></td>
                                    <td class="pe-3 text-end">
                                        @if($a->status !== 'resolved')
                                            <form method="POST" action="{{ route('surveys.alerts.resolve', $a->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Mark this alert as resolved?') }}')">
                                                @csrf
                                                <button class="btn btn-sm btn-light border text-success">
                                                    <i class="ti ti-check"></i> {{ __('Resolve') }}
                                                </button>
                                            </form>
                                        @else
                                            <small class="text-muted">—</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end px-3 py-2">
                    {{ $alerts->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

