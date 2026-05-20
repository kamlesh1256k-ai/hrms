@extends('layouts.admin')
@section('page-title') {{ __('Performance Cycles') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item">{{ __('Cycles') }}</li>
@endsection
@section('action-button')
    @if(in_array(\Illuminate\Support\Facades\Auth::user()->type, ['company','hr']))
        <a href="{{ route('growth-review.cycles.create') }}" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>{{ __('New Cycle') }}</a>
    @endif
@endsection
@section('content')
    @include('growth_review._nav')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    @if(!empty($notEligible) && $notEligible)
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="ti ti-lock fs-5 mt-1"></i>
            <div>
                <strong>{{ __('Not eligible yet') }}</strong><br>
                {{ __('Performance cycles are available only for employees who have completed 6 months at the company.') }}
                @if(!is_null($tenureMonths))
                    <br><small class="text-muted">{{ __('Your current tenure:') }} <strong>{{ (int) $tenureMonths }} {{ __('months') }}</strong>.</small>
                @endif
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Period') }}</th>
                            <th>{{ __('Goal Deadline') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Missions') }}</th>
                            <th>{{ __('Reviews') }}</th>
                            <th>{{ __('Employees') }}</th>
                            <th width="120">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cycles as $c)
                        <tr>
                            <td><strong>{{ $c->name }}</strong></td>
                            <td>{{ $c->start_date->format('d M Y') }} — {{ $c->end_date->format('d M Y') }}</td>
                            <td>{{ $c->goal_deadline ? \Carbon\Carbon::parse($c->goal_deadline)->format('d M Y') : '—' }}</td>
                            <td><span class="cycle-badge cycle-{{ $c->status }}">{{ ucfirst($c->status) }}</span></td>
                            <td>{{ $c->missions()->count() }}</td>
                            <td>{{ $c->reviews()->where('status','submitted')->count() }}</td>
                            <td>{{ $c->assignedEmployees()->count() }}</td>
                            <td>
                                <a href="{{ route('growth-review.cycles.show', $c->id) }}" class="btn btn-sm btn-primary" title="View"><i class="ti ti-eye"></i></a>
                                <a href="{{ route('growth-review.cycles.edit', $c->id) }}" class="btn btn-sm btn-info" title="Edit"><i class="ti ti-edit"></i></a>
                                <form method="POST" action="{{ route('growth-review.cycles.delete', $c->id) }}" class="d-inline" onsubmit="return confirm('Delete this cycle?')">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger" title="Delete"><i class="ti ti-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted">{{ __('No cycles yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>.cycle-badge{font-size:.7rem;padding:3px 10px;border-radius:20px;font-weight:600;}.cycle-active{background:#dcfce7;color:#166534;}.cycle-draft{background:#f3f4f6;color:#6b7280;}.cycle-review{background:#dbeafe;color:#1e40af;}.cycle-completed{background:#e0e7ff;color:#3730a3;}.cycle-calibration{background:#fef3c7;color:#92400e;}</style>
@endsection
