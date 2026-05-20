@extends('layouts.admin')
@section('page-title') {{ __('Shoutouts') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item">{{ __('Shoutouts') }}</li>
@endsection
@push('css-page')
<style>
    .so-card{border:1px solid var(--bs-border-color);border-radius:12px;padding:16px;margin-bottom:12px;background:var(--bs-body-bg);}
    .so-badge{display:inline-block;font-size:.82rem;padding:3px 10px;border-radius:20px;background:#fef3c7;color:#92400e;font-weight:500;}
    .so-from{font-weight:700;color:var(--bs-body-color);} .so-to{font-weight:700;color:#4361ee;}
</style>
@endpush
@section('content')
    @include('growth_review._nav')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="ti ti-speakerphone me-2"></i>{{ __('Give a Shoutout') }}</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('growth-review.shoutouts.store') }}">@csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('To') }} <span class="text-danger">*</span></label>
                            <select name="to_employee_id" class="form-control" required>
                                <option value="">{{ __('Select teammate...') }}</option>
                                @foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Badge') }}</label>
                            <select name="badge" class="form-control">
                                <option value="">{{ __('No badge') }}</option>
                                @foreach($badges as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Message') }} <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="3" required placeholder="Recognize their great work..."></textarea>
                        </div>
                        <button class="btn btn-primary w-100"><i class="ti ti-send me-1"></i>{{ __('Send Shoutout') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('Shoutout Wall') }}</h5></div>
                <div class="card-body">
                    @forelse($shoutouts as $s)
                    <div class="so-card">
                        <div class="d-flex justify-content-between">
                            <div>
                                <span class="so-from">{{ $s->fromEmployee->name ?? '?' }}</span>
                                <i class="ti ti-arrow-right mx-2 text-muted"></i>
                                <span class="so-to">{{ $s->toEmployee->name ?? '?' }}</span>
                                @if($s->badge)<span class="so-badge ms-2">{{ $badges[$s->badge] ?? $s->badge }}</span>@endif
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">{{ $s->created_at->format('d M Y') }}</small>
                                @if(Auth::user()->type == 'company' || Auth::user()->type == 'hr')
                                <form method="POST" action="{{ route('growth-review.shoutouts.delete', $s->id) }}" class="d-inline" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-link text-danger p-0"><i class="ti ti-x"></i></button>
                                </form>
                                @endif
                            </div>
                        </div>
                        <p class="mb-0 mt-2" style="font-size:.88rem;">{{ $s->message }}</p>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4">{{ __('No shoutouts yet. Be the first!') }}</p>
                    @endforelse
                    {{ $shoutouts->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
