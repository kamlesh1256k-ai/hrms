@extends('layouts.admin')
@section('page-title') {{ __('Survey Analytics') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('surveys.index') }}">{{ __('Surveys') }}</a></li>
    <li class="breadcrumb-item">{{ __('Analytics') }}</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">{{ $survey->title }}</h5>
                <small class="text-muted">
                    <span class="badge bg-light text-dark">{{ ucfirst($survey->type) }}</span>
                    <span class="badge bg-light text-dark ms-1">{{ ucfirst($survey->status) }}</span>
                    <span class="ms-2">{{ $survey->responses_count ?? $survey->responses()->count() }} {{ __('responses') }}</span>
                </small>
            </div>
            <div class="d-flex gap-2 flex-wrap justify-content-end">
                @if(Auth::user() && Auth::user()->can('export-surveys'))
                    <a href="{{ route('surveys.export', $survey->id) }}" class="btn btn-light btn-sm border"><i class="ti ti-download me-1"></i>{{ __('CSV') }}</a>
                    <a href="{{ route('surveys.export.pdf', $survey->id) }}" class="btn btn-light btn-sm border"><i class="ti ti-file-text me-1"></i>{{ __('PDF') }}</a>
                @endif
                @if($survey->type === 'pulse')
                    <a href="{{ route('surveys.pulse') }}?survey_id={{ $survey->id }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-chart-line me-1"></i>{{ __('Open in Pulse Trends') }}
                    </a>
                @else
                    <a href="{{ route('surveys.enps') }}?survey_id={{ $survey->id }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-trending-up me-1"></i>{{ __('Open in eNPS Report') }}
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if($enpsSummary['total'] > 0)
                <div class="alert alert-info">
                    <strong>{{ __('eNPS Score') }}:</strong>
                    {{ $enpsSummary['score'] > 0 ? '+' : '' }}{{ number_format($enpsSummary['score'], 1) }}
                    <span class="text-muted">({{ $enpsSummary['total'] }} {{ __('responses') }})</span>
                </div>
            @else
                <div class="alert alert-light">{{ __('No eNPS responses yet for this survey.') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:54px;">{{ __('#') }}</th>
                            <th>{{ __('Question') }}</th>
                            <th style="width:120px;">{{ __('Type') }}</th>
                            <th style="width:110px;" class="text-center">{{ __('Answers') }}</th>
                            <th style="width:110px;" class="text-center">{{ __('Average') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($questionStats ?? []) as $q)
                            <tr>
                                <td class="text-muted">{{ $q['order_no'] }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $q['text'] }}</div>
                                    @if(!empty($q['required']))
                                        <small class="text-muted">{{ __('Required') }}</small>
                                    @endif
                                    @if(!empty($q['options']))
                                        <div class="small text-muted mt-1">
                                            {{ __('Breakdown') }}:
                                            {{ implode(', ', array_map(fn($o) => $o['value'].' ('.$o['total'].')', $q['options'])) }}
                                        </div>
                                    @endif
                                    @if(!empty($q['sentiment']))
                                        <div class="small text-muted mt-1">
                                            {{ __('Sentiment') }}:
                                            {{ implode(', ', array_map(fn($s) => $s['sentiment'].' ('.$s['total'].')', $q['sentiment'])) }}
                                        </div>
                                    @endif
                                </td>
                                <td><span class="badge bg-light text-dark">{{ $q['type'] }}</span></td>
                                <td class="text-center"><strong>{{ (int)($q['total'] ?? 0) }}</strong></td>
                                <td class="text-center">{{ $q['avg'] === null ? '—' : number_format((float)$q['avg'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
