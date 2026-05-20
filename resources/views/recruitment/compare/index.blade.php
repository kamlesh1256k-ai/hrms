@extends('layouts.admin')

@section('page-title') {{ __('Compare Candidates') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item">{{ __('Compare') }}</li>
@endsection

@push('css-page')
<style>
    .cmp-cell { vertical-align: top; padding: 12px; border: 1px solid #e2e8f0; }
    .cmp-attr { background: #f8fafc; font-weight: 600; font-size: .82rem; color: #475569; width: 200px; }
    .cmp-name { font-size: 1.05rem; font-weight: 700; }
    .cmp-stars { color: #f59e0b; }
    .cmp-pill  { display: inline-block; padding: 2px 8px; background: #eef2ff; color: #4338ca; border-radius: 12px; font-size: .72rem; margin: 2px; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0"><i class="ti ti-arrows-shuffle me-1 text-primary"></i>{{ __('Side-by-side Candidate Compare') }}</h6></div>
        <form method="GET" action="{{ route('recruitment.compare') }}" class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">{{ __('Job') }}</label>
                    <select name="job_id" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('-- Select a job --') }}</option>
                        @foreach($jobs as $j)
                            <option value="{{ $j->id }}" @selected($jobId == $j->id)>{{ $j->title }}</option>
                        @endforeach
                    </select>
                </div>
                @if($candidates->isNotEmpty())
                    <div class="col-md-5">
                        <label class="form-label">{{ __('Pick 2–4 candidates') }}</label>
                        <select name="candidates[]" class="form-select" multiple required size="6">
                            @foreach($candidates as $c)
                                <option value="{{ $c->id }}" @selected(in_array($c->id, request('candidates', [])))>
                                    {{ $c->name }} — {{ $c->email }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('Hold Ctrl / Cmd to select multiple.') }}</small>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100"><i class="ti ti-arrows-shuffle me-1"></i>{{ __('Compare') }}</button>
                    </div>
                @endif
            </div>
        </form>
    </div>

    @if($compare->isNotEmpty())
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0" style="border-collapse:separate;">
                        <tbody>
                            <tr>
                                <td class="cmp-attr cmp-cell"></td>
                                @foreach($compare as $c)
                                    <td class="cmp-cell">
                                        <div class="cmp-name">{{ $c->name }}</div>
                                        <div class="text-muted small">{{ $c->email }} · {{ $c->phone }}</div>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="cmp-attr cmp-cell">{{ __('Job') }}</td>
                                @foreach($compare as $c)<td class="cmp-cell">{{ $c->jobs->title ?? '—' }}</td>@endforeach
                            </tr>
                            <tr>
                                <td class="cmp-attr cmp-cell">{{ __('Source') }}</td>
                                @foreach($compare as $c)
                                    <td class="cmp-cell">
                                        @if($c->source) <span class="cmp-pill">{{ \App\Models\JobApplication::$sources[$c->source] ?? $c->source }}</span> @else — @endif
                                        @if($c->source_detail) <div class="small text-muted mt-1">{{ $c->source_detail }}</div> @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="cmp-attr cmp-cell">{{ __('Recruiter') }}</td>
                                @foreach($compare as $c)<td class="cmp-cell">{{ $c->recruiter->name ?? '—' }}</td>@endforeach
                            </tr>
                            <tr>
                                <td class="cmp-attr cmp-cell">{{ __('Rating') }}</td>
                                @foreach($compare as $c)
                                    <td class="cmp-cell cmp-stars">
                                        @for($i=1;$i<=5;$i++)<i class="ti ti-star{{ $i <= $c->rating ? '-filled' : '' }}"></i>@endfor
                                        <span class="text-muted ms-1 small">({{ $c->rating ?? 0 }}/5)</span>
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="cmp-attr cmp-cell">{{ __('Skills') }}</td>
                                @foreach($compare as $c)
                                    <td class="cmp-cell">
                                        @if($c->skill)
                                            @foreach(explode(',', $c->skill) as $sk)
                                                <span class="cmp-pill">{{ trim($sk) }}</span>
                                            @endforeach
                                        @else — @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="cmp-attr cmp-cell">{{ __('Assessments') }}</td>
                                @foreach($compare as $c)
                                    <td class="cmp-cell">
                                        @if($c->assessments->isEmpty()) — @else
                                            @foreach($c->assessments as $a)
                                                <div class="small">
                                                    <strong>{{ $a->title }}</strong>
                                                    @if($a->score !== null && $a->max_score)
                                                        — {{ $a->score }}/{{ $a->max_score }}
                                                        ({{ $a->percentage }}%)
                                                    @endif
                                                    <span class="badge bg-{{ \App\Models\RecruitmentAssessment::$outcomeBadge[$a->outcome] ?? 'secondary' }}">{{ \App\Models\RecruitmentAssessment::$outcomes[$a->outcome] }}</span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            {{-- Interview Rounds (with feedback / meeting links) --}}
                            <tr>
                                <td class="cmp-attr cmp-cell">{{ __('Interview Rounds') }}</td>
                                @foreach($compare as $c)
                                    <td class="cmp-cell">
                                        @if($c->interviews->isEmpty())
                                            <span class="text-muted small">{{ __('No interviews scheduled') }}</span>
                                        @else
                                            @foreach($c->interviews as $iv)
                                                @php
                                                    $roundLabel = \App\Models\InterviewSchedule::$roundTypes[$iv->round_type ?? 'technical'] ?? 'Technical';
                                                    $statusKey  = $iv->status ?? 'scheduled';
                                                    $statusBdg  = \App\Models\InterviewSchedule::$statusBadge[$statusKey] ?? 'info';
                                                    $statusLbl  = \App\Models\InterviewSchedule::$statuses[$statusKey] ?? 'Scheduled';
                                                @endphp
                                                <div class="small mb-2 pb-2 border-bottom">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <strong>
                                                            <i class="ti ti-circle-dot text-primary"></i>
                                                            {{ $roundLabel }}
                                                            @if($iv->round_label)
                                                                <span class="text-muted fw-normal">· {{ $iv->round_label }}</span>
                                                            @endif
                                                        </strong>
                                                        <span class="badge bg-{{ $statusBdg }}" style="font-size:.65rem;">{{ $statusLbl }}</span>
                                                    </div>

                                                    <div class="text-muted" style="font-size:.72rem;">
                                                        <i class="ti ti-calendar"></i>
                                                        {{ \Carbon\Carbon::parse($iv->date)->format('d M Y') }}
                                                        · <i class="ti ti-clock"></i>
                                                        {{ \Carbon\Carbon::parse($iv->time)->format('h:i A') }}
                                                        @if($iv->users)
                                                            · <i class="ti ti-user"></i> {{ $iv->users->name }}
                                                        @endif
                                                    </div>

                                                    @if($iv->mode)
                                                        <div style="font-size:.72rem;">
                                                            @if($iv->mode === 'online') <i class="ti ti-video text-success"></i>
                                                            @elseif($iv->mode === 'phone') <i class="ti ti-phone text-warning"></i>
                                                            @else <i class="ti ti-map-pin text-info"></i>
                                                            @endif
                                                            {{ \App\Models\InterviewSchedule::$modes[$iv->mode] ?? $iv->mode }}
                                                        </div>
                                                    @endif

                                                    @if($iv->meeting_link)
                                                        <a href="{{ $iv->meeting_link }}" target="_blank" rel="noopener"
                                                           class="d-inline-block mt-1" style="font-size:.72rem;word-break:break-all;">
                                                            <i class="ti ti-link"></i> {{ __('Join Meeting') }}
                                                        </a>
                                                    @endif

                                                    @if($iv->rating)
                                                        <div class="text-warning mt-1" style="font-size:.72rem;">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <i class="ti ti-star{{ $i <= $iv->rating ? '-filled' : '' }}"></i>
                                                            @endfor
                                                            ({{ $iv->rating }}/5)
                                                        </div>
                                                    @endif

                                                    @if($iv->recommendation)
                                                        <div style="font-size:.72rem;">
                                                            <strong>{{ __('Recommendation') }}:</strong>
                                                            <span class="text-uppercase">{{ \App\Models\InterviewSchedule::$recommendations[$iv->recommendation] ?? $iv->recommendation }}</span>
                                                        </div>
                                                    @endif

                                                    @if($iv->feedback)
                                                        <div class="mt-1 p-2 rounded bg-light" style="font-size:.7rem;white-space:pre-wrap;line-height:1.4;">
                                                            {{ \Illuminate\Support\Str::limit($iv->feedback, 200) }}
                                                        </div>
                                                    @endif

                                                    @if($iv->comment)
                                                        <div class="text-muted mt-1" style="font-size:.7rem;">
                                                            <i class="ti ti-message"></i> {{ \Illuminate\Support\Str::limit($iv->comment, 120) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="cmp-attr cmp-cell">{{ __('BGV Status') }}</td>
                                @foreach($compare as $c)
                                    @php
                                        $tot = $c->bgvChecks->count();
                                        $cl  = $c->bgvChecks->where('status','cleared')->count();
                                        $fail = $c->bgvChecks->where('status','failed')->count();
                                    @endphp
                                    <td class="cmp-cell">
                                        @if($tot === 0) — @else
                                            <strong>{{ $cl }}</strong> / {{ $tot }} {{ __('cleared') }}
                                            @if($fail > 0) <span class="badge bg-danger ms-1">{{ $fail }} {{ __('failed') }}</span> @endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="cmp-attr cmp-cell">{{ __('Applied') }}</td>
                                @foreach($compare as $c)<td class="cmp-cell">{{ $c->created_at->format('d M Y') }}</td>@endforeach
                            </tr>
                            <tr>
                                <td class="cmp-attr cmp-cell">{{ __('Resume') }}</td>
                                @foreach($compare as $c)
                                    <td class="cmp-cell">
                                        @if($c->resume)
                                            <a href="{{ asset('uploads/job/resume/'.$c->resume) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-file-cv me-1"></i>{{ __('View Resume') }}
                                            </a>
                                        @else — @endif
                                    </td>
                                @endforeach
                            </tr>

                            {{-- Final Evaluation row --}}
                            <tr>
                                <td class="cmp-attr cmp-cell">{{ __('Final Decision') }}</td>
                                @foreach($compare as $c)
                                    <td class="cmp-cell">
                                        <div class="mb-2">
                                            <span class="badge bg-{{ \App\Models\JobApplication::$finalStatusBadge[$c->final_status ?? 'pending'] ?? 'secondary' }} fs-6">
                                                {{ \App\Models\JobApplication::$finalStatuses[$c->final_status ?? 'pending'] ?? 'Pending' }}
                                            </span>
                                            @if($c->final_rank)
                                                <span class="badge bg-primary-subtle text-primary ms-1">#{{ $c->final_rank }}</span>
                                            @endif
                                        </div>
                                        @if($c->final_decided_by && $c->finalDecidedBy)
                                            <small class="text-muted d-block mb-2">
                                                {{ __('by') }} <strong>{{ $c->finalDecidedBy->name }}</strong>
                                                · {{ $c->final_decided_at?->diffForHumans() }}
                                            </small>
                                        @endif

                                        <form method="POST" action="{{ route('recruitment.decisions.mark', $c->id) }}">
                                            @csrf
                                            <div class="d-flex gap-1 mb-2">
                                                <button name="final_status" value="selected" class="btn btn-sm btn-success {{ $c->final_status === 'selected' ? '' : 'btn-outline-success' }}" title="{{ __('Selected') }}">
                                                    <i class="ti ti-check"></i>
                                                </button>
                                                <button name="final_status" value="backup"   class="btn btn-sm {{ $c->final_status === 'backup' ? 'btn-warning' : 'btn-outline-warning' }}" title="{{ __('Backup') }}">
                                                    <i class="ti ti-bookmark"></i>
                                                </button>
                                                <button name="final_status" value="rejected" class="btn btn-sm {{ $c->final_status === 'rejected' ? 'btn-danger' : 'btn-outline-danger' }}" title="{{ __('Rejected') }}">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                            </div>
                                            <input type="number" name="final_rank" value="{{ $c->final_rank }}" min="1" max="99"
                                                   class="form-control form-control-sm mb-1" placeholder="{{ __('Rank #') }}"
                                                   onchange="this.form.querySelector('button[name=final_status][value={{ $c->final_status ?: 'pending' }}]').click()">
                                            <textarea name="final_notes" rows="2" class="form-control form-control-sm"
                                                      placeholder="{{ __('Decision notes…') }}" maxlength="5000">{{ $c->final_notes }}</textarea>
                                            <button type="submit" name="final_status" value="{{ $c->final_status ?: 'pending' }}"
                                                    class="btn btn-sm btn-outline-primary w-100 mt-1">
                                                <i class="ti ti-device-floppy me-1"></i>{{ __('Save') }}
                                            </button>
                                        </form>

                                        @php($offer = $c->offer)
                                        @if($offer)
                                            <a href="{{ route('recruitment.offers.show', $offer->id) }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">
                                                <i class="ti ti-receipt-2 me-1"></i>{{ __('Open Offer') }}
                                            </a>
                                            <div class="text-muted small mt-1">
                                                {{ __('Offer Status:') }}
                                                <strong>{{ \App\Models\JobOnBoard::$statuses[$offer->status] ?? $offer->status }}</strong>
                                                @if($offer->total_ctc)
                                                    Â· {{ $offer->currency ?: 'INR' }} {{ number_format($offer->total_ctc, 0) }}
                                                @endif
                                            </div>
                                        @endif

                                        @if(($c->final_status ?? 'pending') === 'selected' && (!$offer || $offer->status !== 'offer_released'))
                                            <form method="POST" action="{{ route('recruitment.compare.offer_request') }}" class="mt-2">
                                                @csrf
                                                <input type="hidden" name="candidate_id" value="{{ $c->id }}">
                                                <input type="hidden" name="currency" value="{{ $offer?->currency ?: 'INR' }}">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">{{ __('CTC (Annual)') }}</span>
                                                    <input type="number" step="0.01" min="0" name="total_ctc" class="form-control"
                                                           value="{{ old('total_ctc', $offer?->total_ctc) }}" required>
                                                </div>
                                                <button class="btn btn-sm btn-primary w-100 mt-2">
                                                    <i class="ti ti-send me-1"></i>{{ __('Send for CTC Approval') }}
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Internal Discussion Thread (per candidate) ── --}}
        <div class="row mt-4">
            @foreach($compare as $c)
                <div class="col-md-{{ count($compare) <= 2 ? '6' : (count($compare) <= 3 ? '4' : '6') }} mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="ti ti-messages me-1 text-primary"></i>{{ __('Discussion') }} — {{ $c->name }}</h6>
                        </div>
                        <div class="card-body" style="max-height:380px;overflow-y:auto;">
                            @if($c->decisionNotes->isEmpty())
                                <div class="text-center text-muted py-3 small">
                                    <i class="ti ti-message-off" style="font-size:2rem;opacity:.4;"></i>
                                    <div class="mt-2">{{ __('No notes yet — start the discussion below.') }}</div>
                                </div>
                            @else
                                @foreach($c->decisionNotes as $n)
                                    <div class="d-flex gap-2 mb-3 pb-3 border-bottom">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                             style="width:32px;height:32px;font-size:.75rem;font-weight:700;flex-shrink:0;">
                                            {{ strtoupper(substr($n->user->name ?? '?', 0, 1)) }}
                                        </div>
                                        <div class="flex-grow-1" style="min-width:0;">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <strong class="small">{{ $n->user->name ?? __('Unknown') }}</strong>
                                                <span class="text-muted" style="font-size:.7rem;">
                                                    {{ $n->created_at->diffForHumans() }}
                                                    @if((int) $n->user_id === \Auth::id() || in_array(\Auth::user()->type, ['company','super admin']))
                                                        <form method="POST" action="{{ route('recruitment.decisions.notes.delete', $n->id) }}" class="d-inline ms-1" onsubmit="return confirm('Delete this note?')">
                                                            @csrf @method('DELETE')
                                                            <button class="btn btn-sm btn-link text-danger p-0 m-0" style="font-size:.7rem;line-height:1;"><i class="ti ti-trash"></i></button>
                                                        </form>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="small" style="white-space:pre-wrap;word-wrap:break-word;">{{ $n->note }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <form method="POST" action="{{ route('recruitment.decisions.notes.post', $c->id) }}" class="card-footer">
                            @csrf
                            <textarea name="note" rows="2" class="form-control form-control-sm" required maxlength="3000"
                                      placeholder="{{ __('Add a note…') }}"></textarea>
                            <button class="btn btn-sm btn-primary mt-2 w-100">
                                <i class="ti ti-send me-1"></i>{{ __('Post Note') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
