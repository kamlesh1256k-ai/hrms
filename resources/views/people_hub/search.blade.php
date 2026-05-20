@extends('layouts.admin')
@section('page-title') {{ __('Search Crew Member') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('People Hub') }}</li>
    <li class="breadcrumb-item">{{ __('Search') }}</li>
@endsection

@push('css-page')
<style>
    .search-card{border:1px solid #e5e7eb;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:14px;transition:all .15s;cursor:pointer;}
    .search-card:hover{border-color:#6366f1;box-shadow:0 2px 10px rgba(99,102,241,.12);transform:translateY(-1px);}
    .sr-avatar{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;color:#fff;flex-shrink:0;}
    .sr-info{flex:1;min-width:0;}
    .sr-info strong{font-size:.9rem;color:#1f2a44;}
    .sr-info small{color:#94a3b8;font-size:.75rem;}
    .sr-contact{font-size:.75rem;color:#64748b;}
    .sr-contact i{color:#6366f1;margin-right:2px;}
</style>
@endpush

@section('content')
    @include('people_hub._nav')

    <div class="card mb-3">
        <div class="card-body py-3">
            <form class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label mb-1">{{ __('Search by Name, Email, Phone, ID') }}</label>
                    <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="{{ __('Type to search…') }}" autofocus>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">{{ __('Department') }}</label>
                    <select name="department_id" class="form-control">
                        <option value="">{{ __('All') }}</option>
                        @foreach($departments as $d)<option value="{{ $d->id }}" {{ $deptId==$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-search me-1"></i>{{ __('Search') }}</button>
                </div>
                @if($q || $deptId)
                <div class="col-md-2">
                    <a href="{{ route('people-hub.search') }}" class="btn btn-outline-danger w-100"><i class="ti ti-x me-1"></i>{{ __('Clear') }}</a>
                </div>
                @endif
            </form>
        </div>
    </div>

    @if($q || $deptId)
        <p class="text-muted mb-2" style="font-size:.82rem;">{{ $results->count() }} {{ __('result(s) found') }}</p>
    @endif

    <div class="row g-3">
        @forelse($results as $r)
        <div class="col-md-6 col-lg-4">
            <div class="search-card" data-url="{{ route('people-hub.detail', $r->id) }}" data-ajax-popup="true" data-size="lg" data-title="{{ $r->name }}">
                @php $c = ['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#8b5cf6','#ef4444','#06b6d4'][$r->id % 8]; @endphp
                <div class="sr-avatar" style="background:{{ $c }};">{{ strtoupper(substr($r->name, 0, 1)) }}</div>
                <div class="sr-info">
                    <strong>{{ $r->name }}</strong>
                    <br><small>{{ $r->designation->name ?? '—' }} · {{ $r->department->name ?? '—' }}</small>
                    <div class="sr-contact mt-1">
                        @if($r->phone)<span class="me-2"><i class="ti ti-phone"></i>{{ $r->phone }}</span>@endif
                        <span><i class="ti ti-mail"></i>{{ $r->email }}</span>
                    </div>
                </div>
                <span class="badge bg-light text-dark" style="font-size:.68rem;">{{ $r->employee_id }}</span>
            </div>
        </div>
        @empty
            @if($q || $deptId)
            <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-4">{{ __('No results found.') }}</div></div></div>
            @else
            <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-4"><i class="ti ti-search" style="font-size:3rem;color:#e5e7eb;"></i><p class="mt-2">{{ __('Search for crew members by name, email, phone or employee ID.') }}</p></div></div></div>
            @endif
        @endforelse
    </div>
@endsection
