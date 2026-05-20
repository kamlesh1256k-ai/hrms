@extends('layouts.admin')
@section('page-title') {{ __('Reviews') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item">{{ __('Reviews') }}</li>
@endsection
@push('css-page')
<style>
    .rv-badge{font-size:.65rem;padding:2px 8px;border-radius:15px;font-weight:600;display:inline-block;white-space:nowrap;}
    .rv-done{background:#dcfce7;color:#166534;}
    .rv-draft{background:#fef3c7;color:#92400e;}
    .rv-none{background:#f3f4f6;color:#9ca3af;}
    .rv-waiting{background:#eef2ff;color:#4338ca;border:1px dashed #c7d2fe;}
</style>
@endpush
@section('content')
    @include('growth_review._nav')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card mb-3">
        <div class="card-body py-3">
            @if($cycles->isEmpty())
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0">
                    <span class="text-muted"><i class="ti ti-info-circle me-1"></i>{{ __('No performance cycle has been created yet. Create one to start reviews.') }}</span>
                    <a href="{{ route('growth-review.cycles.create') }}" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>{{ __('Create Cycle') }}</a>
                </div>
            @else
                <form class="d-flex align-items-end gap-3 flex-wrap">
                    <div><label class="form-label mb-1">{{ __('Cycle') }}</label>
                        <select name="cycle_id" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:200px;">
                            @foreach($cycles as $c)<option value="{{ $c->id }}" {{ $cycleId==$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    @if($cycle)<span class="text-muted" style="font-size:.82rem;"><i class="ti ti-info-circle me-1"></i>Scale: {{ $cycle->rating_scale ?? '1-5' }}</span>@endif
                </form>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Employee') }}</th>
                            <th class="text-center">{{ __('Self') }}</th>
                            <th class="text-center">{{ __('Manager') }}</th>
                            <th class="text-center">{{ __('Head') }}</th>
                            <th class="text-center">{{ __('Management') }}</th>
                            <th class="text-center">{{ __('Final Rating') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                        @php
                            $empReviews = $reviews[$emp->id] ?? collect();
                            $empRating = $ratings[$emp->id] ?? null;
                            $getReview = function($type) use ($empReviews) { return $empReviews->firstWhere('review_type', $type); };
                            $kpi = $kpiScores[$emp->id] ?? null;
                        @endphp
                        <tr>
                            <td><strong>{{ $emp->name }}</strong><br><small class="text-muted">{{ $emp->designation->name ?? '' }}</small></td>
                            @foreach(['self','manager','head','management'] as $type)
                            @php
                                $rv = $getReview($type);
                                // Fallback to KPI generator weighted score when no
                                // gr_reviews entry is submitted yet. Mapping:
                                //   self → kpi.self, manager → kpi.manager, head → kpi.hod
                                //   (management has no KPI counterpart)
                                $kpiVal = null;
                                if ($kpi) {
                                    if ($type === 'self')        $kpiVal = $kpi['self'];
                                    elseif ($type === 'manager') $kpiVal = $kpi['manager'];
                                    elseif ($type === 'head')    $kpiVal = $kpi['hod'];
                                }
                            @endphp
                            <td class="text-center">
                                @if($rv && $rv->status === 'submitted')
                                    <span class="rv-badge rv-done">{{ $rv->rating ?? '—' }}</span>
                                @elseif($kpiVal !== null && $kpiVal > 0)
                                    <span class="rv-badge rv-done" title="{{ __('From KPI Generator') }}">{{ $kpiVal }}</span>
                                @elseif($rv)
                                    <span class="rv-badge rv-draft">{{ __('Draft') }}</span>
                                @else
                                    <span class="rv-badge rv-waiting">{{ __('Waiting for Review') }}</span>
                                @endif
                            </td>
                            @endforeach
                            <td class="text-center">
                                @if($empRating && $empRating->is_frozen)
                                    <strong class="text-success">{{ $empRating->final_rating }}</strong>
                                    @if($empRating->grade)<br><small class="badge bg-primary">{{ $empRating->grade }}</small>@endif
                                @elseif($empRating && $empRating->final_rating)
                                    <span class="text-warning">{{ $empRating->final_rating }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($cycleId)
                                @php
                                    $viewUser = Auth::user();
                                    $viewEmp = \App\Models\Employee::where('user_id', $viewUser->id)->first();
                                    $viewIsAdmin = in_array($viewUser->type, ['company', 'super admin', 'hr'], true);
                                    $viewIsOwner = $viewEmp && (int)$emp->id === $viewEmp->id;
                                    $viewIsMgr = $viewEmp && (int)($emp->reporting_manager_id ?? 0) === $viewEmp->id;
                                    $viewIsHod = $viewEmp && ((int)($emp->hod_id ?? 0) === $viewEmp->id || (int)($emp->management_id ?? 0) === $viewEmp->id);
                                @endphp
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($viewIsAdmin)
                                        @foreach(['self','manager','head','management'] as $type)
                                        <a href="{{ route('growth-review.reviews.form', [$cycleId, $emp->id, $type]) }}" class="btn btn-sm btn-outline-{{ ($getReview($type) && $getReview($type)->status==='submitted') ? 'success' : 'primary' }}" style="font-size:.65rem;padding:2px 6px;">
                                            {{ ucfirst($type) }}
                                        </a>
                                        @endforeach
                                    @else
                                        @if($viewIsOwner)
                                        <a href="{{ route('growth-review.reviews.form', [$cycleId, $emp->id, 'self']) }}" class="btn btn-sm btn-outline-{{ ($getReview('self') && $getReview('self')->status==='submitted') ? 'success' : 'primary' }}" style="font-size:.65rem;padding:2px 6px;">
                                            {{ ($getReview('self') && $getReview('self')->status==='submitted') ? __('View Review') : __('Self Review') }}
                                        </a>
                                        @endif
                                        @if($viewIsMgr)
                                        <a href="{{ route('growth-review.reviews.form', [$cycleId, $emp->id, 'manager']) }}" class="btn btn-sm btn-outline-{{ ($getReview('manager') && $getReview('manager')->status==='submitted') ? 'success' : 'primary' }}" style="font-size:.65rem;padding:2px 6px;">
                                            {{ ($getReview('manager') && $getReview('manager')->status==='submitted') ? __('View Review') : __('Manager Review') }}
                                        </a>
                                        @endif
                                        @if($viewIsHod)
                                        <a href="{{ route('growth-review.reviews.form', [$cycleId, $emp->id, 'head']) }}" class="btn btn-sm btn-outline-{{ ($getReview('head') && $getReview('head')->status==='submitted') ? 'success' : 'primary' }}" style="font-size:.65rem;padding:2px 6px;">
                                            {{ ($getReview('head') && $getReview('head')->status==='submitted') ? __('View Review') : __('HOD Review') }}
                                        </a>
                                        @endif
                                        {{-- View all submitted reviews (read-only) --}}
                                        @if($viewIsOwner && $empReviews->where('status', 'submitted')->count() > 0)
                                        <a href="{{ route('growth-review.reviews.form', [$cycleId, $emp->id, 'self']) }}" class="btn btn-sm btn-outline-info" style="font-size:.65rem;padding:2px 6px;" title="{{ __('View all reviews given to you') }}">
                                            <i class="ti ti-eye me-1"></i>{{ __('View Reviews') }}
                                        </a>
                                        @endif
                                    @endif
                                </div>
                                @else
                                <span class="text-muted" style="font-size:.7rem;">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted">{{ __('No employees found.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
