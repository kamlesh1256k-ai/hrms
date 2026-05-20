@php
    // Compute notification counts once per render — helper caches for 60s.
    $rnNotif = \App\Support\RecruitmentNotifications::summary();
    $rnCounts = $rnNotif['counts'];
@endphp

@push('css-page')
<style>
    .nav-pills .nav-link .nav-badge {
        display: inline-block; min-width: 18px; padding: 1px 6px;
        background: #ef4444; color: #fff; border-radius: 10px;
        font-size: .65rem; font-weight: 700; line-height: 1.4;
        margin-left: 4px; vertical-align: top;
    }
    .nav-pills .nav-link.active .nav-badge { background: #fff; color: #4361ee; }
</style>
@endpush

<ul class="nav nav-pills mb-3" role="tablist" style="gap:4px;flex-wrap:wrap;">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('recruitment.dashboard') ? 'active' : '' }}"
           href="{{ route('recruitment.dashboard') }}">
            <i class="ti ti-layout-dashboard me-1"></i>{{ __('Dashboard') }}
            @if($rnNotif['total'] > 0)
                <span class="nav-badge">{{ $rnNotif['total'] }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('recruitment.requisitions.*') ? 'active' : '' }}"
           href="{{ route('recruitment.requisitions.index') }}">
            <i class="ti ti-file-plus me-1"></i>{{ __('Requisitions') }}
            @if($rnCounts['requisitions'] > 0)
                <span class="nav-badge">{{ $rnCounts['requisitions'] }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('recruitment.bgv.*') ? 'active' : '' }}"
           href="{{ route('recruitment.bgv.index') }}">
            <i class="ti ti-shield-check me-1"></i>{{ __('Background Verification') }}
            @if($rnCounts['bgv_pending'] > 0)
                <span class="nav-badge">{{ $rnCounts['bgv_pending'] }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('recruitment.preonboarding.*') ? 'active' : '' }}"
           href="{{ route('recruitment.preonboarding.index') }}">
            <i class="ti ti-checklist me-1"></i>{{ __('Pre-Onboarding') }}
            @if($rnCounts['preonboarding_pending'] > 0)
                <span class="nav-badge">{{ $rnCounts['preonboarding_pending'] }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('recruitment.assessments.*') ? 'active' : '' }}"
           href="{{ route('recruitment.assessments.index') }}">
            <i class="ti ti-clipboard-text me-1"></i>{{ __('Assessments') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('recruitment.probation.*') ? 'active' : '' }}"
           href="{{ route('recruitment.probation.index') }}">
            <i class="ti ti-user-check me-1"></i>{{ __('Probation') }}
            @if($rnCounts['probation_due'] > 0)
                <span class="nav-badge">{{ $rnCounts['probation_due'] }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('recruitment.compare') ? 'active' : '' }}"
           href="{{ route('recruitment.compare') }}">
            <i class="ti ti-arrows-shuffle me-1"></i>{{ __('Compare') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('recruitment.decisions.*') ? 'active' : '' }}"
           href="{{ route('recruitment.decisions.index') }}">
            <i class="ti ti-gavel me-1"></i>{{ __('Final Decisions') }}
            @if($rnCounts['decisions_pending'] > 0)
                <span class="nav-badge">{{ $rnCounts['decisions_pending'] }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('recruitment.offers.*') ? 'active' : '' }}"
           href="{{ route('recruitment.offers.index') }}">
            <i class="ti ti-receipt-2 me-1"></i>{{ __('Offers') }}
            @php $offersBadge = $rnCounts['offers_awaiting_approval'] + $rnCounts['offers_negotiation']; @endphp
            @if($offersBadge > 0)
                <span class="nav-badge">{{ $offersBadge }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('recruitment.talent-pool.*') ? 'active' : '' }}"
           href="{{ route('recruitment.talent-pool.index') }}">
            <i class="ti ti-users me-1"></i>{{ __('Talent Pool') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('recruitment.analytics') ? 'active' : '' }}"
           href="{{ route('recruitment.analytics') }}">
            <i class="ti ti-chart-bar me-1"></i>{{ __('Analytics') }}
        </a>
    </li>
    @if(in_array(\Auth::user()->type, ['company','hr','super admin']))
        @if(\Route::has('job.index'))
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('job.index') || request()->routeIs('job.create') || request()->routeIs('job.edit') || request()->routeIs('job.show') ? 'active' : '' }}"
               href="{{ route('job.index') }}">
                <i class="ti ti-briefcase me-1"></i>{{ __('Job Openings') }}
            </a>
        </li>
        @endif
        @if(\Route::has('job-application.index'))
        <li class="nav-item">
            <a class="nav-link {{ request()->is('job-application*') ? 'active' : '' }}"
               href="{{ route('job-application.index') }}">
                <i class="ti ti-users me-1"></i>{{ __('Candidates') }}
            </a>
        </li>
        @endif
        @if(\Route::has('interview-schedule.index'))
        <li class="nav-item">
            <a class="nav-link {{ request()->is('interview-schedule*') ? 'active' : '' }}"
               href="{{ route('interview-schedule.index') }}">
                <i class="ti ti-calendar-event me-1"></i>{{ __('Interviews') }}
                @if($rnCounts['interviews_to_feedback'] > 0)
                    <span class="nav-badge">{{ $rnCounts['interviews_to_feedback'] }}</span>
                @endif
            </a>
        </li>
        @endif
        @if(\Route::has('job.on.board'))
        <li class="nav-item">
            <a class="nav-link {{ request()->is('job-onboard*') ? 'active' : '' }}"
               href="{{ route('job.on.board') }}">
                <i class="ti ti-clipboard-check me-1"></i>{{ __('Onboarding') }}
            </a>
        </li>
        @endif
    @endif
</ul>
