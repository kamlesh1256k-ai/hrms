@extends('layouts.admin')
@section('page-title') {{ ucfirst($type) }} {{ __('Review') }} — {{ $employee->name }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.reviews', ['cycle_id' => $cycle->id]) }}">{{ __('Reviews') }}</a></li>
    <li class="breadcrumb-item">{{ ucfirst($type) }} Review</li>
@endsection
@push('css-page')
<style>
    .rv-mission{border:1px solid var(--bs-border-color);border-radius:10px;padding:14px;margin-bottom:10px;}
    .star-rating{display:inline-flex;gap:2px;} .star-rating .star{font-size:1.4rem;color:#d1d5db;transition:.15s;} .star-rating .star.active{color:#f59e0b;}
    .star-rating:not(.readonly) .star{cursor:pointer;} .star-rating:not(.readonly) .star:hover{color:#fbbf24;}
    .rv-card{border:1px solid #e5e7eb;border-radius:12px;padding:16px 20px;margin-bottom:12px;}
    .rv-card-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
    .rv-type-badge{font-size:.72rem;padding:3px 12px;border-radius:20px;font-weight:600;}
    .rv-self{background:#ede9fe;color:#6d28d9;}.rv-manager{background:#fef3c7;color:#92400e;}.rv-head{background:#cffafe;color:#0e7490;}.rv-management{background:#fce7f3;color:#9d174d;}
    .rv-field{margin-bottom:8px;}
    .rv-field label{font-size:.72rem;color:#94a3b8;font-weight:600;text-transform:uppercase;display:block;margin-bottom:2px;}
    .rv-field p{margin:0;color:#1f2a44;font-size:.88rem;}
</style>
@endpush
@section('content')
    @include('growth_review._nav')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card mb-3" style="border-left:4px solid #4361ee;">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h6 class="mb-0">{{ $employee->name }} <small class="text-muted">— {{ $employee->designation->name ?? '' }}</small></h6>
                    <small class="text-muted">{{ $cycle->name }} · {{ ucfirst($type) }} Review</small>
                </div>
                @if($review && $review->status === 'submitted')
                <span class="badge bg-success">{{ __('Submitted') }} {{ $review->submitted_at?->format('d M Y') }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- All Submitted Reviews (Read-Only) --}}
    @if($allReviews->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header py-2"><h6 class="mb-0"><i class="ti ti-clipboard-check me-1"></i>{{ __('All Reviews') }}</h6></div>
        <div class="card-body">
            @foreach($allReviews as $rType => $rv)
            <div class="rv-card">
                <div class="rv-card-head">
                    <span class="rv-type-badge rv-{{ $rType }}"><i class="ti ti-user me-1"></i>{{ ucfirst($rType) }} {{ __('Review') }}</span>
                    <div class="d-flex align-items-center gap-2">
                        <strong style="font-size:1.2rem;color:#1f2a44;">{{ $rv->rating ?? '—' }}<small class="text-muted">/{{ $cycle->rating_scale === '1-10' ? '10' : '5' }}</small></strong>
                        <small class="text-muted">{{ $rv->submitted_at?->format('d M Y') }}</small>
                    </div>
                </div>
                @if($rv->strengths)
                <div class="rv-field"><label>{{ __('Strengths') }}</label><p>{{ $rv->strengths }}</p></div>
                @endif
                @if($rv->improvements)
                <div class="rv-field"><label>{{ __('Areas for Improvement') }}</label><p>{{ $rv->improvements }}</p></div>
                @endif
                @if($rv->comments)
                <div class="rv-field"><label>{{ __('Comments') }}</label><p>{{ $rv->comments }}</p></div>
                @endif

                {{-- Mission-level ratings from this review --}}
                @if($rv->ratings_json && $missions->isNotEmpty())
                <div class="mt-2" style="border-top:1px dashed #e5e7eb;padding-top:8px;">
                    <small class="text-muted fw-bold">{{ __('Mission Ratings:') }}</small>
                    @php $rvRatings = collect($rv->ratings_json)->keyBy('mission_id'); @endphp
                    @foreach($missions as $m)
                    @php $mr = $rvRatings[$m->id] ?? null; @endphp
                    @if($mr)
                    <div class="d-flex justify-content-between align-items-center py-1" style="font-size:.82rem;">
                        <span>{{ $m->title }} @if($m->weightage > 0)<small class="text-muted">({{ $m->weightage }}%)</small>@endif</span>
                        <div class="star-rating readonly">
                            @for($i = 1; $i <= 5; $i++)
                            <span class="star {{ ($mr['rating'] ?? 0) >= $i ? 'active' : '' }}" style="font-size:1rem;">&#9733;</span>
                            @endfor
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Edit Form (only if canEdit) --}}
    @if($canEdit ?? false)
    <form method="POST" action="{{ route('growth-review.reviews.store') }}" id="reviewForm">@csrf
        <input type="hidden" name="cycle_id" value="{{ $cycle->id }}">
        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
        <input type="hidden" name="review_type" value="{{ $type }}">
        <input type="hidden" name="ratings_json" id="ratingsJsonField" value="">

        {{-- Mission-level ratings --}}
        @if($missions->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0"><i class="ti ti-target me-2"></i>{{ __('Rate Missions') }}</h5></div>
            <div class="card-body">
                @foreach($missions as $m)
                @php $mRating = ($review && $review->ratings_json) ? collect($review->ratings_json)->firstWhere('mission_id', $m->id) : null; @endphp
                <div class="rv-mission">
                    <div class="d-flex justify-content-between">
                        <div><strong>{{ $m->title }}</strong> @if($m->weightage>0)<small class="text-muted">({{ $m->weightage }}%)</small>@endif</div>
                        <div class="star-rating" data-mission="{{ $m->id }}">
                            @for($i = 1; $i <= 5; $i++)
                            <span class="star {{ ($mRating && $mRating['rating'] >= $i) ? 'active' : '' }}" data-val="{{ $i }}">&#9733;</span>
                            @endfor
                            <input type="hidden" class="mission-rating-val" data-mid="{{ $m->id }}" value="{{ $mRating['rating'] ?? 0 }}">
                        </div>
                    </div>
                    @if($m->kpi)<small class="text-info"><i class="ti ti-chart-bar me-1"></i>{{ $m->kpi }}</small>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0"><i class="ti ti-clipboard-check me-2"></i>{{ __('Overall Assessment') }}</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Overall Rating') }} <span class="text-danger">*</span></label>
                        <input type="number" name="rating" class="form-control" min="0" max="{{ $cycle->rating_scale === '1-10' ? 10 : 5 }}" step="0.5" value="{{ $review->rating ?? '' }}" required>
                        <small class="text-muted">Scale: {{ $cycle->rating_scale ?? '1-5' }}</small>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">{{ __('Comments') }}</label>
                        <textarea name="comments" class="form-control" rows="2">{{ $review->comments ?? '' }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Strengths') }}</label>
                        <textarea name="strengths" class="form-control" rows="3">{{ $review->strengths ?? '' }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Areas for Improvement') }}</label>
                        <textarea name="improvements" class="form-control" rows="3">{{ $review->improvements ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" name="action" value="draft" class="btn btn-secondary"><i class="ti ti-device-floppy me-1"></i>{{ __('Save Draft') }}</button>
            <button type="submit" name="action" value="submit" class="btn btn-primary" onclick="return confirm('Submit review? This cannot be undone.')"><i class="ti ti-send me-1"></i>{{ __('Submit Review') }}</button>
            <a href="{{ route('growth-review.reviews', ['cycle_id' => $cycle->id]) }}" class="btn btn-outline-secondary ms-auto">{{ __('Back') }}</a>
        </div>
    </form>
    @else
    <div class="text-center mt-3">
        <a href="{{ route('growth-review.reviews', ['cycle_id' => $cycle->id]) }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>{{ __('Back to Reviews') }}</a>
    </div>
    @endif
@endsection

@push('script-page')
<script>
document.querySelectorAll('.star-rating:not(.readonly)').forEach(function(container) {
    container.querySelectorAll('.star').forEach(function(star) {
        star.addEventListener('click', function() {
            var val = parseInt(this.dataset.val);
            var input = container.querySelector('.mission-rating-val');
            input.value = val;
            container.querySelectorAll('.star').forEach(function(s) {
                s.classList.toggle('active', parseInt(s.dataset.val) <= val);
            });
        });
    });
});

var form = document.getElementById('reviewForm');
if (form) {
    form.addEventListener('submit', function() {
        var ratings = [];
        document.querySelectorAll('.mission-rating-val').forEach(function(inp) {
            if (parseInt(inp.value) > 0) {
                ratings.push({ mission_id: parseInt(inp.dataset.mid), rating: parseInt(inp.value) });
            }
        });
        document.getElementById('ratingsJsonField').value = JSON.stringify(ratings);
    });
}
</script>
@endpush
