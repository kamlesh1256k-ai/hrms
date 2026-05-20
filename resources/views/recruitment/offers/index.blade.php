@extends('layouts.admin')

@section('page-title') {{ __('Offer Management') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item">{{ __('Offers') }}</li>
@endsection

@push('css-page')
<style>
    .of-stat-pill {
        display: inline-block; padding: 8px 14px; border-radius: 20px;
        font-size: .8rem; font-weight: 600; margin: 2px 4px 2px 0; cursor: pointer;
        background: #f1f5f9; color: #475569; text-decoration: none;
    }
    .of-stat-pill:hover { background: #e2e8f0; }
    .of-stat-pill.active { background: #4361ee; color: #fff; }
    .of-stat-pill .badge { margin-left: 6px; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="card mb-3">
        <div class="card-body py-3">
            <h6 class="mb-2"><i class="ti ti-files me-1 text-primary"></i>{{ __('Offer Pipeline') }}</h6>
            <a href="{{ route('recruitment.offers.index') }}"
               class="of-stat-pill {{ !$status ? 'active' : '' }}">
                {{ __('All') }} <span class="badge bg-light text-dark">{{ $statusCounts->sum() }}</span>
            </a>
            @foreach(\App\Models\JobOnBoard::$statuses as $key => $label)
                @if(in_array($key, ['pending','awaiting_approval','offer_released','negotiation','accepted','declined']))
                    <a href="{{ route('recruitment.offers.index', ['status' => $key]) }}"
                       class="of-stat-pill {{ $status === $key ? 'active' : '' }}">
                        {{ $label }} <span class="badge bg-light text-dark">{{ $statusCounts[$key] ?? 0 }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if($offers->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="ti ti-file-off" style="font-size:3rem;opacity:.4;"></i>
                    <p class="mt-2 mb-0">{{ __('No offers in this view.') }}</p>
                    <small>{{ __('Offers are created from the Job Application → Onboard flow.') }}</small>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Candidate') }}</th>
                                <th>{{ __('Job') }}</th>
                                <th>{{ __('CTC') }}</th>
                                <th>{{ __('Joining') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Released') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($offers as $o)
                                <tr>
                                    <td>
                                        <strong>{{ $o->applications->name ?? '—' }}</strong><br>
                                        <small class="text-muted">{{ $o->applications->email ?? '' }}</small>
                                    </td>
                                    <td>{{ $o->applications->jobs->title ?? '—' }}</td>
                                    <td>
                                        @if($o->total_ctc)
                                            <strong>{{ $o->currency ?? 'INR' }} {{ number_format($o->total_ctc, 0) }}</strong>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $o->joining_date ? $o->joining_date->format('d M Y') : '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ \App\Models\JobOnBoard::$statusBadge[$o->status] ?? 'secondary' }}">
                                            {{ \App\Models\JobOnBoard::$statuses[$o->status] ?? ucfirst($o->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($o->offer_released_at)
                                            <small class="text-muted">{{ $o->offer_released_at->diffForHumans() }}</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('recruitment.offers.show', $o->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-eye me-1"></i>{{ __('Manage') }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $offers->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
