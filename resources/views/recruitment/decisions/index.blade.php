@extends('layouts.admin')

@section('page-title') {{ __('Final Decisions') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item">{{ __('Final Decisions') }}</li>
@endsection

@push('css-page')
<style>
    .dec-col-head {
        padding: 12px 16px; border-radius: 10px 10px 0 0; color: #fff; font-weight: 700;
        display: flex; justify-content: space-between; align-items: center;
    }
    .dec-col-head.selected { background: linear-gradient(135deg, #10b981, #059669); }
    .dec-col-head.backup   { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .dec-col-head.pending  { background: linear-gradient(135deg, #64748b, #475569); }
    .dec-col-head.rejected { background: linear-gradient(135deg, #ef4444, #b91c1c); }
    .dec-col {
        background: #f8fafc; border-radius: 0 0 10px 10px;
        padding: 8px; min-height: 280px;
    }
    .dec-card {
        background: #fff; border-radius: 8px; padding: 10px 12px; margin-bottom: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06); transition: transform .12s, box-shadow .12s;
    }
    .dec-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
    .dec-name { font-weight: 600; font-size: .88rem; }
    .dec-job  { font-size: .72rem; color: #64748b; }
    .dec-meta { font-size: .68rem; color: #94a3b8; margin-top: 4px; }
    .dec-rank { font-weight: 700; color: #4338ca; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0"><i class="ti ti-gavel me-1 text-primary"></i>{{ __('Final Evaluation & Decisions') }}</h6></div>
        <form method="GET" action="{{ route('recruitment.decisions.index') }}" class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">{{ __('Filter by Job') }}</label>
                    <select name="job_id" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('-- All jobs --') }}</option>
                        @foreach($jobs as $j)
                            <option value="{{ $j->id }}" @selected($jobId == $j->id)>{{ $j->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7 text-end">
                    <a href="{{ route('recruitment.compare') }}{{ $jobId ? '?job_id='.$jobId : '' }}" class="btn btn-primary">
                        <i class="ti ti-arrows-shuffle me-1"></i>{{ __('Open Compare View') }}
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- 4-column kanban-style status board --}}
    <div class="row g-3">
        @foreach (['selected','backup','pending','rejected'] as $status)
            @php $rows = $grouped->get($status, collect()); @endphp
            <div class="col-md-3">
                <div class="dec-col-head {{ $status }}">
                    <span>
                        @if($status === 'selected')      <i class="ti ti-circle-check"></i>
                        @elseif($status === 'backup')    <i class="ti ti-bookmark"></i>
                        @elseif($status === 'pending')   <i class="ti ti-clock"></i>
                        @else                            <i class="ti ti-circle-x"></i>
                        @endif
                        {{ \App\Models\JobApplication::$finalStatuses[$status] }}
                    </span>
                    <span class="badge bg-light text-dark">{{ $rows->count() }}</span>
                </div>
                <div class="dec-col">
                    @if($rows->isEmpty())
                        <div class="text-center text-muted py-4" style="font-size:.8rem;">{{ __('No candidates') }}</div>
                    @else
                        @foreach($rows as $c)
                            <div class="dec-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1" style="min-width:0;">
                                        <div class="dec-name">{{ $c->name }}</div>
                                        <div class="dec-job">{{ $c->jobs->title ?? '—' }}</div>
                                        @if($c->rating)
                                            <div class="text-warning" style="font-size:.72rem;">
                                                @for($i=1;$i<=5;$i++)<i class="ti ti-star{{ $i <= $c->rating ? '-filled' : '' }}"></i>@endfor
                                            </div>
                                        @endif
                                        @if($c->final_notes)
                                            <div class="dec-meta" style="white-space:pre-wrap;">{{ \Illuminate\Support\Str::limit($c->final_notes, 80) }}</div>
                                        @endif
                                        @if($c->finalDecidedBy)
                                            <div class="dec-meta">
                                                <i class="ti ti-user"></i> {{ $c->finalDecidedBy->name }}
                                                · {{ $c->final_decided_at?->diffForHumans() }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        @if($c->final_rank)
                                            <span class="dec-rank">#{{ $c->final_rank }}</span><br>
                                        @endif
                                        <a href="{{ route('recruitment.compare', ['job_id' => $c->job, 'candidates[]' => $c->id]) }}"
                                           class="btn btn-sm btn-link p-0 mt-1" title="{{ __('Open in Compare') }}">
                                            <i class="ti ti-external-link"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
