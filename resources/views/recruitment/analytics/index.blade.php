@extends('layouts.admin')

@section('page-title') {{ __('Recruitment Analytics') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item">{{ __('Analytics') }}</li>
@endsection

@push('css-page')
<style>
    .an-bar { background:#eef2ff; border-radius:4px; height:12px; overflow:hidden; }
    .an-bar > span { display:block; height:100%; background:linear-gradient(90deg,#4361ee,#7c3aed); }
    .an-funnel-stage {
        display: grid; grid-template-columns: 200px 1fr 80px; gap: 12px; align-items: center;
        padding: 8px 14px; background: #fff; border-bottom: 1px solid #f1f5f9;
    }
    .an-funnel-bar { background: linear-gradient(90deg,#10b981,#059669); height: 28px; border-radius: 4px;
                     color:#fff; padding: 4px 10px; font-size:.78rem; font-weight:600; min-width: 30px; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="row">
        {{-- Source of hire --}}
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-pie-chart me-1 text-primary"></i>{{ __('Candidates by Source') }}</h6></div>
                <div class="card-body">
                    @if($sources->isEmpty())
                        <div class="text-center text-muted py-4">{{ __('No data') }}</div>
                    @else
                        @php $maxSource = max($sources->max('total'), 1); @endphp
                        @foreach($sources as $s)
                            @php
                                $label = \App\Models\JobApplication::$sources[$s->source] ?? ucfirst($s->source);
                                $hires = $hiresBySource[$s->source] ?? 0;
                                $pct   = round(($s->total / $maxSource) * 100);
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-semibold">{{ $label }}</span>
                                    <span class="small text-muted">
                                        {{ $s->total }} {{ __('applied') }}
                                        @if($hires) · <strong class="text-success">{{ $hires }} {{ __('hired') }}</strong> @endif
                                    </span>
                                </div>
                                <div class="an-bar"><span style="width: {{ $pct }}%"></span></div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        {{-- Recruiter leaderboard --}}
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="ti ti-trophy me-1 text-warning"></i>{{ __('Recruiter Leaderboard') }}</h6></div>
                <div class="card-body p-0">
                    @if($recruiters->isEmpty())
                        <div class="text-center text-muted py-4">{{ __('No data') }}</div>
                    @else
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light"><tr><th>#</th><th>{{ __('Recruiter') }}</th><th class="text-end">{{ __('Candidates') }}</th></tr></thead>
                            <tbody>
                                @foreach($recruiters as $i => $r)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $recruiterUsers[$r->recruiter_id] ?? __('Unknown') }}</td>
                                        <td class="text-end"><strong>{{ $r->candidates }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Hiring funnel --}}
    <div class="card">
        <div class="card-header"><h6 class="mb-0"><i class="ti ti-funnel me-1 text-primary"></i>{{ __('Hiring Funnel — Candidates per Stage') }}</h6></div>
        <div class="card-body p-0">
            @if($funnel->isEmpty())
                <div class="text-center text-muted py-4">{{ __('No funnel data') }}</div>
            @else
                @php $maxF = max($funnel->max('total'), 1); @endphp
                @foreach($funnel as $stage)
                    @php $w = max(2, round(($stage->total / $maxF) * 100)); @endphp
                    <div class="an-funnel-stage">
                        <span class="fw-semibold small">{{ $stage->title }}</span>
                        <div class="an-funnel-bar" style="width: {{ $w }}%; line-height: 20px;">
                            @if($stage->total > 0) {{ $stage->total }} @endif
                        </div>
                        <span class="text-muted small text-end">{{ $stage->total }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
