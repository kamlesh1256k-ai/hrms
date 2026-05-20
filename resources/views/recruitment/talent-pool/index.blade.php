@extends('layouts.admin')

@section('page-title') {{ __('Talent Pool') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item">{{ __('Talent Pool') }}</li>
@endsection

@push('css-page')
<style>
    .tp-stat-pill { display:inline-block; padding:6px 12px; border-radius:20px;
                    font-size:.78rem; font-weight:600; margin:2px 4px 2px 0;
                    background:#f1f5f9; color:#475569; text-decoration:none; cursor:pointer; }
    .tp-stat-pill:hover { background:#e2e8f0; }
    .tp-stat-pill.active { background:#4361ee; color:#fff; }
    .tp-stat-pill .badge { margin-left:6px; }
    .tp-skill-pill { display:inline-block; background:#eef2ff; color:#4338ca;
                     padding:2px 8px; border-radius:12px; font-size:.7rem; margin:1px 2px; }
    .tp-tag-pill { display:inline-block; background:#fef3c7; color:#92400e;
                   padding:2px 8px; border-radius:12px; font-size:.7rem; margin:1px 2px; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0"><i class="ti ti-users me-1 text-primary"></i>{{ __('Talent Pool') }}</h6>
            <div class="d-flex gap-2">
                <a href="{{ route('recruitment.talent-pool.match') }}" class="btn btn-outline-primary btn-sm">
                    <i class="ti ti-target me-1"></i>{{ __('Match for Job') }}
                </a>
                <a href="{{ route('recruitment.talent-pool.create') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i>{{ __('Add Candidate') }}
                </a>
            </div>
        </div>
        <div class="card-body py-3">
            {{-- Filter pills --}}
            <div class="mb-3">
                <a href="{{ route('recruitment.talent-pool.index') }}"
                   class="tp-stat-pill {{ !request('status') ? 'active' : '' }}">
                    {{ __('All') }} <span class="badge bg-light text-dark">{{ $statusCounts->sum() }}</span>
                </a>
                @foreach(\App\Models\TalentPoolCandidate::$statuses as $key => $label)
                    <a href="{{ route('recruitment.talent-pool.index', ['status' => $key]) }}"
                       class="tp-stat-pill {{ request('status') === $key ? 'active' : '' }}">
                        {{ $label }} <span class="badge bg-light text-dark">{{ $statusCounts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>

            {{-- Search bar --}}
            <form method="GET" class="row g-2">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <div class="col-md-6">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                           placeholder="{{ __('Search by name, email, skills, company, or tag…') }}">
                </div>
                <div class="col-md-3">
                    <select name="source" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ __('All sources') }}</option>
                        @foreach(\App\Models\TalentPoolCandidate::$sources as $k => $label)
                            <option value="{{ $k }}" @selected(request('source') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-sm btn-outline-secondary"><i class="ti ti-search me-1"></i>{{ __('Search') }}</button>
                    @if(request('q') || request('source') || request('status'))
                        <a href="{{ route('recruitment.talent-pool.index') }}" class="btn btn-sm btn-link">{{ __('Clear') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            @if($candidates->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="ti ti-user-off" style="font-size:3rem;opacity:.4;"></i>
                    <p class="mt-2 mb-0">{{ __('No candidates in talent pool.') }}</p>
                    <small>{{ __('Add candidates manually or import from job applications.') }}</small>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Candidate') }}</th>
                                <th>{{ __('Company / Role') }}</th>
                                <th>{{ __('Experience') }}</th>
                                <th>{{ __('Skills') }}</th>
                                <th>{{ __('Source') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Last Engaged') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($candidates as $c)
                                <tr>
                                    <td>
                                        <strong>{{ $c->name }}</strong><br>
                                        <small class="text-muted">{{ $c->email }}</small>
                                        @if(!empty($c->tags_array))
                                            <div class="mt-1">
                                                @foreach($c->tags_array as $tag)
                                                    <span class="tp-tag-pill">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($c->current_company || $c->current_designation)
                                            <strong>{{ $c->current_designation ?: '—' }}</strong>
                                            @if($c->current_company)
                                                <br><small class="text-muted">@ {{ $c->current_company }}</small>
                                            @endif
                                        @else — @endif
                                    </td>
                                    <td>{{ $c->experience_years ? $c->experience_years.' yrs' : '—' }}</td>
                                    <td>
                                        @foreach(array_slice($c->skills_array, 0, 4) as $sk)
                                            <span class="tp-skill-pill">{{ $sk }}</span>
                                        @endforeach
                                        @if(count($c->skills_array) > 4)
                                            <span class="text-muted small">+{{ count($c->skills_array) - 4 }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ \App\Models\TalentPoolCandidate::$sources[$c->source] ?? $c->source }}</small>
                                        @if($c->source_detail)
                                            <br><small class="text-primary" style="font-size:.7rem;">{{ $c->source_detail }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ \App\Models\TalentPoolCandidate::$statusBadge[$c->status] ?? 'secondary' }}">
                                            {{ \App\Models\TalentPoolCandidate::$statuses[$c->status] ?? $c->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $c->last_engaged_at ? $c->last_engaged_at->diffForHumans() : '—' }}
                                        </small>
                                    </td>
                                    <td>
                                        <a href="{{ route('recruitment.talent-pool.show', $c->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $candidates->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
