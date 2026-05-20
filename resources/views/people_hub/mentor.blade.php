@extends('layouts.admin')
@section('page-title') {{ __('Mentor Buddy / Growth Partner') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('People Hub') }}</li>
    <li class="breadcrumb-item">{{ __('Mentor Buddy') }}</li>
@endsection

@push('css-page')
<style>
    .mentor-card{border:1px solid #e5e7eb;border-radius:14px;padding:20px;transition:all .15s;}
    .mentor-card:hover{border-color:#ec4899;box-shadow:0 4px 14px rgba(236,72,153,.12);}
    .mentor-avatar{width:60px;height:60px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:1.5rem;color:#fff;}
    .mentee-chip{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border:1px solid #e5e7eb;border-radius:10px;cursor:pointer;transition:all .12s;}
    .mentee-chip:hover{border-color:#6366f1;background:#faf5ff;}
</style>
@endpush

@section('content')
    @include('people_hub._nav')

    @if(!$emp)
        <div class="alert alert-warning"><i class="ti ti-alert-triangle me-1"></i>{{ __('Your account is not linked to an employee record.') }}</div>
    @else
        <div class="row g-4">
            {{-- My Mentor --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="ti ti-heart-handshake me-1 text-danger"></i>{{ __('My Mentor / Growth Partner') }}</h6></div>
                    <div class="card-body text-center">
                        @if($myMentor)
                            <div class="mentor-card d-inline-block" data-url="{{ route('people-hub.detail', $myMentor->id) }}" data-ajax-popup="true" data-size="lg" data-title="{{ $myMentor->name }}" style="cursor:pointer;">
                                <div class="mentor-avatar" style="background:linear-gradient(135deg,#ec4899,#f43f5e);">{{ strtoupper(substr($myMentor->name, 0, 1)) }}</div>
                                <h5 class="mt-2 mb-0">{{ $myMentor->name }}</h5>
                                <small class="text-muted">{{ $myMentor->designation->name ?? '—' }} · {{ $myMentor->department->name ?? '—' }}</small>
                                <div class="mt-2" style="font-size:.82rem;">
                                    @if($myMentor->phone)<span class="me-3"><i class="ti ti-phone text-primary me-1"></i>{{ $myMentor->phone }}</span>@endif
                                    <span><i class="ti ti-mail text-primary me-1"></i>{{ $myMentor->email }}</span>
                                </div>
                            </div>
                        @else
                            <div class="text-muted py-4">
                                <i class="ti ti-heart-handshake" style="font-size:3rem;color:#e5e7eb;"></i>
                                <p class="mt-2 mb-0">{{ __('No mentor assigned yet. Contact HR to get a mentor buddy.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- My Mentees --}}
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="ti ti-users me-1 text-success"></i>{{ __('People I Mentor') }} <span class="badge bg-success">{{ $myMentees->count() }}</span></h6></div>
                    <div class="card-body">
                        @if($myMentees->isEmpty())
                            <div class="text-center text-muted py-4">{{ __('You are not assigned as mentor to anyone yet.') }}</div>
                        @else
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($myMentees as $mentee)
                                <div class="mentee-chip" data-url="{{ route('people-hub.detail', $mentee->id) }}" data-ajax-popup="true" data-size="lg" data-title="{{ $mentee->name }}">
                                    @php $c = ['#6366f1','#ec4899','#10b981','#f59e0b','#3b82f6'][$loop->index % 5]; @endphp
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white" style="width:32px;height:32px;font-weight:700;font-size:.8rem;background:{{ $c }};">{{ strtoupper(substr($mentee->name, 0, 1)) }}</span>
                                    <div>
                                        <strong style="font-size:.85rem;">{{ $mentee->name }}</strong>
                                        <br><small class="text-muted">{{ $mentee->designation->name ?? '—' }}</small>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Admin: Assign Mentor --}}
        @if($isAdmin)
        <div class="card mt-3">
            <div class="card-header"><h6 class="mb-0"><i class="ti ti-settings me-1"></i>{{ __('Assign Mentor Buddy') }}</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('people-hub.mentor.assign') }}" class="row g-3 align-items-end">@csrf
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Employee') }}</label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">{{ __('Select Employee') }}</option>
                            @foreach($allEmployees as $e)<option value="{{ $e->id }}">{{ $e->name }} ({{ $e->employee_id }})</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Mentor / Growth Partner') }}</label>
                        <select name="mentor_buddy_id" class="form-control" required>
                            <option value="">{{ __('Select Mentor') }}</option>
                            @foreach($allEmployees as $e)<option value="{{ $e->id }}">{{ $e->name }} ({{ $e->employee_id }})</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="ti ti-check me-1"></i>{{ __('Assign') }}</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    @endif
@endsection
