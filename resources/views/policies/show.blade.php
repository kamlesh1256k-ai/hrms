@extends('layouts.admin')
@section('page-title') {{ $policy->title }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('policies.index') }}">{{ __('Policies') }}</a></li>
    <li class="breadcrumb-item">{{ $policy->title }}</li>
@endsection

@push('css-page')
<style>
    .pdf-frame{width:100%;height:760px;border:1px solid #e2e8f0;border-radius:10px;background:#fafafa;}
    .ack-card{border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;background:#fff;}
    .ack-card.is-done{border-left:4px solid #10b981;background:linear-gradient(135deg,#f0fdf4,#fff 60%);}
    .ack-card.is-pending{border-left:4px solid #f59e0b;background:linear-gradient(135deg,#fef3c7,#fff 60%);}
    .ack-card .lbl{font-size:.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.4px;font-weight:600;}
    .ack-card .val{font-size:1.05rem;font-weight:700;color:#0f172a;margin-top:3px;}

    .pol-meta{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:6px;}
    .pol-meta .pol-cat-badge{font-size:.68rem;font-weight:700;padding:3px 9px;border-radius:6px;text-transform:uppercase;letter-spacing:.3px;}
    .pol-cat-hr{background:#dbeafe;color:#1d4ed8;}
    .pol-cat-leave{background:#dcfce7;color:#166534;}
    .pol-cat-it{background:#ede9fe;color:#6d28d9;}
    .pol-cat-conduct{background:#fef3c7;color:#b45309;}
    .pol-cat-other{background:#f1f5f9;color:#475569;}
    .pol-meta .badge-ver{background:#f1f5f9;color:#475569;font-size:.7rem;font-weight:700;padding:3px 8px;border-radius:6px;}
    .pol-meta .badge-mand{background:#fee2e2;color:#991b1b;font-size:.65rem;font-weight:700;padding:3px 8px;border-radius:6px;text-transform:uppercase;}
</style>
@endpush

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('info'))<div class="alert alert-info alert-dismissible fade show">{{ session('info') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-1"><i class="ti ti-file-text me-2"></i>{{ $policy->title }}</h5>
                    <div class="pol-meta">
                        <span class="pol-cat-badge pol-cat-{{ $policy->category }}">{{ $policy->categoryLabel() }}</span>
                        <span class="badge-ver">v{{ $policy->version }}</span>
                        @if($policy->is_mandatory)
                            <span class="badge-mand">{{ __('Mandatory') }}</span>
                        @endif
                        <small class="text-muted ms-2">
                            <i class="ti ti-calendar me-1"></i>{{ __('Uploaded') }} {{ $policy->created_at->format('d M Y') }}
                        </small>
                    </div>
                    @if($policy->description)
                        <p class="mb-0 mt-2 small text-muted">{{ $policy->description }}</p>
                    @endif
                </div>
                <div class="card-body">
                    @php $fileUrl = route('policies.file', $policy->id); @endphp
                    @if($policy->file_mime === 'application/pdf')
                        <iframe src="{{ $fileUrl }}#toolbar=0&navpanes=0&scrollbar=1&view=FitH" class="pdf-frame" title="{{ $policy->title }}"></iframe>
                    @else
                        <div class="alert alert-light border d-flex justify-content-between align-items-center">
                            <div>
                                <i class="ti ti-file me-1"></i>
                                <strong>{{ $policy->file_name }}</strong>
                                <small class="text-muted ms-2">({{ $policy->file_mime }})</small>
                            </div>
                            <a href="{{ $fileUrl }}" target="_blank" class="btn btn-primary btn-sm">
                                <i class="ti ti-download me-1"></i>{{ __('Download') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- ── Acknowledge card ─────────────────────────── --}}
            <div class="ack-card mb-3 {{ $ack ? 'is-done' : 'is-pending' }}">
                @if($ack)
                    <div class="lbl"><i class="ti ti-check text-success me-1"></i>{{ __('Acknowledged') }}</div>
                    <div class="val">{{ $ack->acknowledged_at->format('d M Y · h:i A') }}</div>
                    <small class="text-muted d-block mt-2">
                        {{ __('Recorded :ago', ['ago' => $ack->acknowledged_at->diffForHumans()]) }}
                    </small>
                @else
                    <div class="lbl"><i class="ti ti-clock text-warning me-1"></i>{{ __('Pending Acknowledgement') }}</div>
                    <div class="val mb-2">{{ __('Please review and acknowledge') }}</div>
                    <p class="small text-muted">
                        {{ __('By clicking Acknowledge you confirm that you have read and understood this policy.') }}
                    </p>
                    <form method="POST" action="{{ route('policies.acknowledge', $policy->id) }}"
                          onsubmit="return confirm('{{ __('Confirm: I have read and understood this policy.') }}')">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="ti ti-check me-1"></i>{{ __('Acknowledge') }}
                        </button>
                    </form>
                @endif
            </div>

            {{-- ── HR-only: ack stats + recent acks ─────────── --}}
            @if(Auth::user() && Auth::user()->can('manage-policies'))
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="ti ti-users me-1"></i>{{ __('Acknowledgement Audit') }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">{{ __('Total acknowledgements') }}</span>
                            <strong class="text-primary">{{ $totalAcks }}</strong>
                        </div>
                        @if($recentAcks->isEmpty())
                            <small class="text-muted">{{ __('No acknowledgements yet.') }}</small>
                        @else
                            <div style="max-height: 320px; overflow:auto;">
                                <table class="table table-sm align-middle mb-0">
                                    <thead style="background:#fafafa;">
                                        <tr style="font-size:.7rem;text-transform:uppercase;color:#64748b;">
                                            <th class="ps-2 py-2">{{ __('Employee') }}</th>
                                            <th class="pe-2">{{ __('When') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentAcks as $a)
                                            <tr>
                                                <td class="ps-2"><small>{{ optional($a->user)->name ?? '—' }}</small></td>
                                                <td class="pe-2"><small class="text-muted">{{ $a->acknowledged_at?->diffForHumans() ?? '—' }}</small></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <a href="{{ route('policies.index') }}" class="btn btn-light border w-100">
                <i class="ti ti-arrow-left me-1"></i>{{ __('Back to Policies') }}
            </a>
        </div>
    </div>
@endsection
