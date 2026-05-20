@extends('layouts.admin')
@section('page-title') {{ __('Sync Ups') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item">{{ __('Sync Ups') }}</li>
@endsection
@section('content')
    @include('growth_review._nav')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="ti ti-messages me-2"></i>{{ __('New Sync Up') }}</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('growth-review.sync-ups.store') }}">@csrf
                        <div class="mb-3"><label class="form-label">{{ __('Employee') }} <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-control" required><option value="">{{ __('Select...') }}</option>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select></div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6"><label class="form-label">{{ __('Date') }} <span class="text-danger">*</span></label><input type="date" name="meeting_date" class="form-control" required value="{{ date('Y-m-d') }}"></div>
                            <div class="col-md-6"><label class="form-label">{{ __('Cycle') }}</label>
                                <select name="cycle_id" class="form-control"><option value="">{{ __('None') }}</option>@foreach($cycles as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                        </div>
                        <div class="mb-3"><label class="form-label">{{ __('Discussion Points') }}</label><textarea name="discussion_points" class="form-control" rows="3" placeholder="One point per line..."></textarea></div>
                        <div class="mb-3"><label class="form-label">{{ __('Action Items') }}</label><textarea name="action_items" class="form-control" rows="3" placeholder="One item per line..."></textarea></div>
                        <div class="mb-3"><label class="form-label">{{ __('Notes') }}</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                        <button class="btn btn-primary w-100"><i class="ti ti-check me-1"></i>{{ __('Record Sync Up') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('Sync Up History') }}</h5></div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Employee') }}</th><th>{{ __('Manager') }}</th><th>{{ __('Points') }}</th><th>{{ __('Status') }}</th><th>{{ __('Action') }}</th></tr></thead>
                            <tbody>
                                @forelse($syncUps as $su)
                                <tr>
                                    <td>{{ $su->meeting_date->format('d M Y') }}</td>
                                    <td>{{ $su->employee->name ?? '—' }}</td>
                                    <td>{{ $su->manager->name ?? '—' }}</td>
                                    <td>
                                        @if($su->discussion_points)<span class="badge bg-info">{{ count($su->discussion_points) }} points</span>@endif
                                        @if($su->action_items)<span class="badge bg-warning">{{ count($su->action_items) }} actions</span>@endif
                                    </td>
                                    <td><span class="badge bg-{{ $su->status==='completed'?'success':($su->status==='cancelled'?'danger':'primary') }}">{{ ucfirst($su->status) }}</span></td>
                                    <td>
                                        <form method="POST" action="{{ route('growth-review.sync-ups.delete', $su->id) }}" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger"><i class="ti ti-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @if($su->notes)<tr><td colspan="6" class="ps-4 text-muted" style="font-size:.82rem;border-top:0;"><i class="ti ti-note me-1"></i>{{ $su->notes }}</td></tr>@endif
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('No sync ups recorded.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $syncUps->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
