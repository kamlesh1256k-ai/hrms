@extends('layouts.admin')
@section('page-title') {{ __('My Assigned KRA / KPI') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item">{{ __('My Assigned KRA / KPI') }}</li>
@endsection

@push('css-page')
<style>
    .cycle-accordion{margin-bottom:16px;border:1px solid #e2e5ec;border-radius:14px;overflow:hidden;}
    .cycle-acc-head{
        background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 50%,#a855f7 100%);
        color:#fff;padding:16px 22px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;
        user-select:none;transition:background .15s;
    }
    .cycle-acc-head:hover{background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 50%,#9333ea 100%);}
    .cycle-acc-head h6{margin:0;font-weight:700;font-size:1rem;}
    .cycle-acc-head .cycle-meta{display:flex;gap:14px;align-items:center;flex-wrap:wrap;}
    .cycle-acc-head .cycle-pill{background:rgba(255,255,255,.2);padding:3px 12px;border-radius:20px;font-size:.72rem;font-weight:600;}
    .cycle-acc-head .cycle-status{padding:3px 12px;border-radius:20px;font-size:.72rem;font-weight:600;}
    .cycle-acc-head .cs-active{background:rgba(255,255,255,.25);}.cycle-acc-head .cs-draft{background:rgba(255,255,255,.12);}
    .cycle-acc-head .cs-completed{background:rgba(255,255,255,.3);}
    .cycle-acc-head .toggle-icon{font-size:1.2rem;transition:transform .25s;}
    .cycle-acc-head.collapsed .toggle-icon{transform:rotate(-90deg);}
    .cycle-acc-body{padding:0;background:#fff;}
    .cycle-acc-body.show{display:block;}
    .cycle-acc-body:not(.show){display:none;}
    .cycle-info-bar{display:flex;gap:20px;flex-wrap:wrap;padding:14px 22px;background:#f8fafc;border-bottom:1px solid #f1f5f9;font-size:.82rem;}
    .cycle-info-bar .ci-item label{display:block;font-size:.65rem;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.3px;}
    .cycle-info-bar .ci-item span{font-weight:600;color:#1f2a44;}

    .assigned-card{border:1px solid #e2e5ec;border-radius:12px;margin:14px 18px;overflow:hidden;}
    .assigned-head{background:linear-gradient(135deg,#3b82f6,#60a5fa);color:#fff;padding:12px 18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;}
    .assigned-head h6{margin:0;font-weight:700;font-size:.95rem;}
    .assigned-head small{opacity:.9;}
    .assigned-body{padding:14px 18px;}
    .kra-mini{border-left:3px solid #8b5cf6;padding:10px 14px;margin-bottom:10px;background:#fafbfc;border-radius:6px;}
    .kra-mini h6{margin:0 0 4px;font-size:.88rem;color:#1f2a44;}
    .kra-mini .wt{background:#8b5cf6;color:#fff;padding:1px 10px;border-radius:12px;font-size:.7rem;font-weight:700;float:right;}
    .kpi-row{display:flex;justify-content:space-between;font-size:.8rem;padding:3px 0;color:#475569;}
    .kpi-row strong{color:#6d28d9;}

    .no-cycle-card{margin:14px 18px;padding:14px;border:1px solid #e2e5ec;border-radius:10px;background:#f8fafc;}
</style>
@endpush

@section('content')
    @include('growth_review._nav')

    @if(($orphanCount ?? 0) > 0 && ($isAdmin ?? false))
        <div class="alert alert-warning py-2" style="font-size:.85rem;">
            <i class="ti ti-alert-triangle me-1"></i>
            {{ __(':n assignment(s) point to a deleted KPI sheet and were skipped.', ['n' => $orphanCount]) }}
        </div>
    @endif

    @if(!($isAdmin ?? false) && !$emp)
        <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-1"></i>{{ __('Your account is not linked to an employee record. Please contact HR.') }}
        </div>
    @elseif($assignments->isEmpty())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-inbox" style="font-size:3rem;color:#cbd5e1;"></i>
                <p class="mt-3 mb-0">
                    @if($isAdmin ?? false)
                        {{ __('No KRA / KPI has been assigned to any employee yet.') }}
                    @else
                        {{ __('No KRA / KPI has been assigned to you yet.') }}
                    @endif
                </p>
            </div>
        </div>
    @else
        @php $firstCycle = true; @endphp
        @foreach($assignmentsByCycle as $cyId => $cyAssignments)
            @php
                $cy = $cycles[$cyId] ?? null;
                $isOpen = $firstCycle;
                $firstCycle = false;
            @endphp
            <div class="cycle-accordion">
                <div class="cycle-acc-head {{ $isOpen ? '' : 'collapsed' }}" data-toggle-cycle="{{ $cyId }}">
                    <div>
                        <h6>
                            <i class="ti ti-repeat me-1"></i>
                            {{ $cy ? $cy->name : __('Unlinked KPIs') }}
                        </h6>
                        @if($cy)
                        <small style="opacity:.85;">{{ $cy->start_date->format('d M Y') }} — {{ $cy->end_date->format('d M Y') }}</small>
                        @endif
                    </div>
                    <div class="cycle-meta">
                        @if($cy)
                        <span class="cycle-status cs-{{ $cy->status }}">{{ ucfirst($cy->status) }}</span>
                        @endif
                        <span class="cycle-pill">{{ $cyAssignments->count() }} {{ __('KPI(s)') }}</span>
                        <i class="ti ti-chevron-down toggle-icon"></i>
                    </div>
                </div>
                <div class="cycle-acc-body {{ $isOpen ? 'show' : '' }}" id="cycleBody{{ $cyId }}">
                    @if($cy)
                    <div class="cycle-info-bar">
                        <div class="ci-item"><label>{{ __('Goal Deadline') }}</label><span class="{{ $cy->goal_deadline && $cy->goal_deadline->isPast() ? 'text-danger' : '' }}">{{ $cy->goal_deadline ? $cy->goal_deadline->format('d M Y') : '—' }}</span></div>
                        <div class="ci-item"><label>{{ __('Self Review') }}</label><span>{{ $cy->self_review_start ? $cy->self_review_start->format('d M') . ' – ' . $cy->self_review_end?->format('d M') : '—' }}</span></div>
                        <div class="ci-item"><label>{{ __('Manager Review') }}</label><span>{{ $cy->manager_review_start ? $cy->manager_review_start->format('d M') . ' – ' . $cy->manager_review_end?->format('d M') : '—' }}</span></div>
                        <div class="ci-item"><label>{{ __('Rating Scale') }}</label><span>{{ $cy->rating_scale ?? '1-5' }}</span></div>
                    </div>
                    @endif

                    @foreach($cyAssignments as $a)
                        @php
                            $gen = $a->generation;
                            $kras = $gen && $gen->content_json ? (json_decode($gen->content_json, true) ?? []) : [];
                        @endphp
                        @if($gen)
                        <div class="assigned-card">
                            <div class="assigned-head">
                                <div>
                                    <h6><i class="ti ti-target me-1"></i>{{ $gen->job_role }}</h6>
                                    <small>
                                        @if(($isAdmin ?? false) && $a->employee)
                                            <i class="ti ti-user me-1"></i><strong>{{ $a->employee->name }}</strong> ·
                                        @endif
                                        {{ __('Assigned') }} {{ $a->assigned_at?->diffForHumans() }}
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('growth-review.kpi-generator.show', $gen->id) }}" class="btn btn-sm btn-light" title="{{ __('View Full Review') }}"><i class="ti ti-eye me-1"></i>{{ __('View Review') }}</a>
                                    <a href="{{ route('growth-review.kpi-generator.pdf', $gen->id) }}" class="btn btn-sm btn-light" title="{{ __('Download PDF') }}"><i class="ti ti-download"></i></a>
                                </div>
                            </div>
                            <div class="assigned-body">
                                @if($a->remarks)
                                    <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;"><i class="ti ti-message me-1"></i>{{ $a->remarks }}</div>
                                @endif
                                @foreach($kras as $idx => $k)
                                <div class="kra-mini">
                                    <span class="wt">{{ $k['weightage'] }}%</span>
                                    <h6>KRA {{ $idx + 1 }}: {{ $k['kra'] }}</h6>
                                    <div class="text-muted" style="font-size:.78rem;margin-bottom:6px;">{{ $k['description'] }}</div>
                                    @foreach($k['kpis'] as $kpi)
                                    <div class="kpi-row">
                                        <span>{{ $kpi['metric'] }} <small class="text-muted">({{ $kpi['frequency'] }})</small></span>
                                        <strong>{{ $kpi['target'] }}</strong>
                                    </div>
                                    @endforeach
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
@endsection

@push('script-page')
<script>
document.querySelectorAll('.cycle-acc-head').forEach(function(head){
    head.addEventListener('click', function(){
        var id = this.dataset.toggleCycle;
        var body = document.getElementById('cycleBody' + id);
        if (!body) return;
        var isOpen = body.classList.contains('show');
        body.classList.toggle('show', !isOpen);
        this.classList.toggle('collapsed', isOpen);
    });
});
</script>
@endpush
