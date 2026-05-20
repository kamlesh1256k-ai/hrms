@extends('layouts.admin')

@section('page-title') {{ __('Match Talent for Job') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.talent-pool.index') }}">{{ __('Talent Pool') }}</a></li>
    <li class="breadcrumb-item">{{ __('Match for Job') }}</li>
@endsection

@push('css-page')
<style>
    .mt-bar { background:#eef2ff; border-radius:4px; height:8px; overflow:hidden; min-width:80px; }
    .mt-bar > span { display:block; height:100%; background:linear-gradient(90deg,#10b981,#059669); }
    .mt-skill-pill { display:inline-block; background:#eef2ff; color:#4338ca;
                     padding:2px 8px; border-radius:12px; font-size:.7rem; margin:1px 2px; }
    .mt-skill-pill.matched { background:#d1fae5; color:#065f46; }
    .mt-target-pill { display:inline-block; background:#dbeafe; color:#1e40af;
                      padding:3px 10px; border-radius:14px; font-size:.78rem; margin:2px 3px; font-weight:600; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0"><i class="ti ti-target me-1 text-primary"></i>{{ __('Match Talent Pool to a Job') }}</h6></div>
        <form method="GET" class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Pick a Job') }}</label>
                    <select name="job_id" class="form-select" onchange="this.form.submit()" required>
                        <option value="">{{ __('-- Select a job --') }}</option>
                        @foreach($jobs as $j)
                            <option value="{{ $j->id }}" @selected($job && $job->id === $j->id)>{{ $j->title }}</option>
                        @endforeach
                    </select>
                </div>
                @if($job)
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Required Skills (from job)') }}</label>
                        <div>
                            @forelse($targets as $t)
                                <span class="mt-target-pill">{{ $t }}</span>
                            @empty
                                <span class="text-muted small">{{ __('No skills configured on this job.') }}</span>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </form>
    </div>

    @if($job && !empty($targets))
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    {{ __('Top Matches') }}
                    <span class="badge bg-primary-subtle text-primary ms-1">{{ $matches->count() }}</span>
                </h6>
                <small class="text-muted">{{ __('Sorted by skill-overlap score (active / contacted / interested only).') }}</small>
            </div>
            <div class="card-body p-0">
                @if($matches->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="ti ti-search-off" style="font-size:3rem;opacity:.4;"></i>
                        <p class="mt-2 mb-0">{{ __('No matches found in talent pool.') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Candidate') }}</th>
                                    <th>{{ __('Skills') }}</th>
                                    <th>{{ __('Experience') }}</th>
                                    <th>{{ __('Expected CTC') }}</th>
                                    <th>{{ __('Match Score') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matches as $c)
                                    @php
                                        $score = (int) $c->getAttribute('_match_score');
                                        $candSkillsLower = array_map('strtolower', $c->skills_array);
                                        $targetsLower    = array_map('strtolower', $targets);
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $c->name }}</strong><br>
                                            <small class="text-muted">{{ $c->current_designation ?? '' }}@if($c->current_company) @ {{ $c->current_company }}@endif</small>
                                        </td>
                                        <td>
                                            @foreach(array_slice($c->skills_array, 0, 6) as $sk)
                                                @php
                                                    $isMatch = false;
                                                    foreach ($targetsLower as $t) {
                                                        if (strtolower($sk) === $t || str_contains(strtolower($sk), $t) || str_contains($t, strtolower($sk))) {
                                                            $isMatch = true; break;
                                                        }
                                                    }
                                                @endphp
                                                <span class="mt-skill-pill {{ $isMatch ? 'matched' : '' }}">{{ $sk }}</span>
                                            @endforeach
                                            @if(count($c->skills_array) > 6)
                                                <span class="text-muted small">+{{ count($c->skills_array) - 6 }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $c->experience_years ? $c->experience_years.' yrs' : '—' }}</td>
                                        <td>{{ $c->expected_ctc ? '₹'.number_format($c->expected_ctc, 0) : '—' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="mt-bar"><span style="width: {{ $score }}%"></span></div>
                                                <strong>{{ $score }}%</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('recruitment.talent-pool.show', $c->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-eye me-1"></i>{{ __('View') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @elseif($job && empty($targets))
        <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-1"></i>
            {{ __('This job has no skills configured. Add skills on the job-edit page to enable matching.') }}
        </div>
    @else
        <div class="text-center py-5 text-muted">
            <i class="ti ti-target" style="font-size:3rem;opacity:.4;"></i>
            <p class="mt-2 mb-0">{{ __('Select a job above to find matching candidates from the talent pool.') }}</p>
        </div>
    @endif
</div>
@endsection
