@extends('layouts.admin')
@section('page-title') {{ __('My Squad — Team Members') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('People Hub') }}</li>
    <li class="breadcrumb-item">{{ __('My Squad') }}</li>
@endsection

@push('css-page')
<style>
    .squad-card{border:1px solid #e5e7eb;border-radius:14px;padding:18px;text-align:center;transition:all .15s;cursor:pointer;}
    .squad-card:hover{border-color:#6366f1;box-shadow:0 4px 14px rgba(99,102,241,.12);transform:translateY(-2px);}
    .sq-avatar{width:56px;height:56px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:1.3rem;color:#fff;margin-bottom:8px;}
    .sq-name{font-weight:700;font-size:.92rem;color:#1f2a44;margin-bottom:2px;}
    .sq-role{font-size:.75rem;color:#94a3b8;}
    .sq-contact{font-size:.75rem;color:#64748b;margin-top:6px;}
    .sq-contact i{color:#6366f1;margin-right:3px;}
    .mgr-card{border-left:4px solid #f59e0b;}
    .me-card{border-left:4px solid #6366f1;background:#faf5ff;}
</style>
@endpush

@section('content')
    @include('people_hub._nav')

    @if(!$emp)
        <div class="alert alert-warning"><i class="ti ti-alert-triangle me-1"></i>{{ __('Your account is not linked to an employee record.') }}</div>
    @else
        {{-- My Manager --}}
        @if($manager)
        <h6 class="mb-2"><i class="ti ti-arrow-up me-1 text-warning"></i>{{ __('My Manager') }}</h6>
        <div class="row mb-4">
            <div class="col-md-4 col-sm-6">
                <div class="squad-card mgr-card" data-url="{{ route('people-hub.detail', $manager->id) }}" data-ajax-popup="true" data-size="lg" data-title="{{ $manager->name }}">
                    <div class="sq-avatar" style="background:#f59e0b;">{{ strtoupper(substr($manager->name, 0, 1)) }}</div>
                    <div class="sq-name">{{ $manager->name }}</div>
                    <div class="sq-role">{{ $manager->designation->name ?? '—' }} · {{ $manager->department->name ?? '—' }}</div>
                    <div class="sq-contact">
                        @if($manager->phone)<span><i class="ti ti-phone"></i>{{ $manager->phone }}</span>@endif
                        <span><i class="ti ti-mail"></i>{{ $manager->email }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Me --}}
        <h6 class="mb-2"><i class="ti ti-user me-1 text-primary"></i>{{ __('Me') }}</h6>
        <div class="row mb-4">
            <div class="col-md-4 col-sm-6">
                <div class="squad-card me-card">
                    <div class="sq-avatar" style="background:#6366f1;">{{ strtoupper(substr($emp->name, 0, 1)) }}</div>
                    <div class="sq-name">{{ $emp->name }}</div>
                    <div class="sq-role">{{ $emp->designation->name ?? '—' }} · {{ $emp->department->name ?? '—' }}</div>
                </div>
            </div>
        </div>

        {{-- My Team --}}
        <h6 class="mb-2"><i class="ti ti-users me-1 text-success"></i>{{ __('My Direct Reports') }} <span class="badge bg-primary">{{ $team->count() }}</span></h6>
        @if($team->isEmpty())
            <div class="card"><div class="card-body text-center text-muted py-4">{{ __('No direct reports.') }}</div></div>
        @else
            <div class="row g-3">
                @foreach($team as $member)
                <div class="col-md-4 col-sm-6">
                    <div class="squad-card" data-url="{{ route('people-hub.detail', $member->id) }}" data-ajax-popup="true" data-size="lg" data-title="{{ $member->name }}">
                        @php $c = ['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#8b5cf6'][$loop->index % 6]; @endphp
                        <div class="sq-avatar" style="background:{{ $c }};">{{ strtoupper(substr($member->name, 0, 1)) }}</div>
                        <div class="sq-name">{{ $member->name }}</div>
                        <div class="sq-role">{{ $member->designation->name ?? '—' }} · {{ $member->department->name ?? '—' }}</div>
                        <div class="sq-contact">
                            @if($member->phone)<span><i class="ti ti-phone"></i>{{ $member->phone }}</span><br>@endif
                            <span><i class="ti ti-mail"></i>{{ $member->email }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    @endif
@endsection
