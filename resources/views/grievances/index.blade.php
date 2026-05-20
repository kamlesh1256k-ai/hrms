@extends('layouts.admin')

@section('page-title')
    {{ __('Grievance Management') }}
@endsection

@push('css-page')
    <style>
        .grievance-card {
            border: 1px solid rgba(15, 23, 42, .06);
            border-radius: 12px;
            background: #fff;
            transition: transform .15s ease, box-shadow .15s ease;
            margin-bottom: 16px;
        }
        .grievance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px -8px rgba(15, 23, 42, .12);
        }
        .grievance-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .grievance-category {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            background: #f1f5f9;
            color: #475569;
        }
        .grievance-meta {
            font-size: 0.85rem;
            color: #64748b;
        }
        .stats-card {
            background: linear-gradient(135deg, var(--gradient-from, #6366f1), var(--gradient-to, #8b5cf6));
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stats-card h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        .stats-card p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        .anonymous-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="ti ti-message-circle me-2"></i>
                            {{ __('Grievance Management') }}
                        </h4>
                        @if (Auth::user()->type === 'employee' || in_array(Auth::user()->type, ['super admin', 'company', 'hr']))
                            <a href="{{ route('grievances.track') }}" target="_blank" class="btn btn-light border me-2" title="{{ __('Track an anonymous complaint by token') }}">
                                <i class="ti ti-shield-lock me-1"></i>
                                {{ __('Track Anonymous') }}
                            </a>
                            <a href="{{ route('grievances.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus me-1"></i>
                                {{ __('Raise Grievance') }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Statistics Cards for HR/Admin -->
                    @if (!empty($stats))
                        <div class="row mb-4">
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="stats-card" style="--gradient-from: #6366f1; --gradient-to: #8b5cf6;">
                                    <h3>{{ $stats['total'] }}</h3>
                                    <p>{{ __('Total Grievances') }}</p>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="stats-card" style="--gradient-from: #ef4444; --gradient-to: #f87171;">
                                    <h3>{{ $stats['open'] }}</h3>
                                    <p>{{ __('Open') }}</p>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="stats-card" style="--gradient-from: #f59e0b; --gradient-to: #fbbf24;">
                                    <h3>{{ $stats['in_progress'] }}</h3>
                                    <p>{{ __('In Progress') }}</p>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="stats-card" style="--gradient-from: #10b981; --gradient-to: #34d399;">
                                    <h3>{{ $stats['resolved'] }}</h3>
                                    <p>{{ __('Resolved') }}</p>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="stats-card" style="--gradient-from: #8b5cf6; --gradient-to: #a78bfa;">
                                    <h3>{{ $stats['anonymous'] }}</h3>
                                    <p>{{ __('Anonymous') }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" action="{{ route('grievances.index') }}" class="d-flex gap-2 align-items-end">
                                <div class="flex-grow-1">
                                    <input type="text" name="search" value="{{ request('search') }}" 
                                           class="form-control" placeholder="Search grievances...">
                                </div>
                                <div>
                                    <select name="status" class="form-select">
                                        <option value="">{{ __('All Status') }}</option>
                                        @foreach (App\Models\Grievance::getStatuses() as $status => $label)
                                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <select name="category" class="form-select">
                                        <option value="">{{ __('All Categories') }}</option>
                                        @foreach (App\Models\Grievance::getCategories() as $category => $label)
                                            <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="ti ti-search"></i>
                                    {{ __('Search') }}
                                </button>
                                <a href="{{ route('grievances.index') }}" class="btn btn-outline-secondary">
                                    <i class="ti ti-refresh"></i>
                                    {{ __('Reset') }}
                                </a>
                            </form>
                        </div>
                    </div>

                    <!-- Grievances List -->
                    @if ($grievances->count() > 0)
                        <div class="row">
                            @foreach ($grievances as $grievance)
                                <div class="col-lg-6">
                                    <div class="grievance-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <a href="{{ route('grievances.show', $grievance->id) }}" 
                                                           class="text-decoration-none">
                                                            {{ \Illuminate\Support\Str::limit($grievance->title, 60) }}
                                                        </a>
                                                        @if ($grievance->is_anonymous)
                                                            <span class="anonymous-badge ms-2">Anonymous</span>
                                                        @endif
                                                    </h6>
                                                    <div class="grievance-category mb-2">
                                                        {{ $grievance->category }}
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <span class="grievance-status badge bg-{{ $grievance->status_with_color['color'] }}">
                                                        {{ $grievance->status_with_color['label'] }}
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <p class="text-muted mb-2">
                                                {{ \Illuminate\Support\Str::limit($grievance->description, 120) }}
                                            </p>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="grievance-meta">
                                                    <i class="ti ti-user me-1"></i>
                                                    {{ $grievance->complainant_name }}
                                                    @if ($grievance->assigned_to)
                                                        <span class="ms-3">
                                                            <i class="ti ti-user-check me-1"></i>
                                                            {{ $grievance->assignedTo->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="grievance-meta">
                                                    <i class="ti ti-calendar me-1"></i>
                                                    {{ $grievance->created_at->format('M d, Y') }}
                                                </div>
                                            </div>
                                            
                                            @if ($grievance->latestResponse)
                                                <div class="mt-2 pt-2 border-top">
                                                    <small class="text-muted">
                                                        <i class="ti ti-message me-1"></i>
                                                        Last response: {{ $grievance->latestResponse->created_at->diffForHumans() }}
                                                    </small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $grievances->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ti ti-message-circle-off" style="font-size: 4rem; color: #cbd5e1;"></i>
                            <h5 class="mt-3 text-muted">{{ __('No grievances found') }}</h5>
                            <p class="text-muted">
                                @if (Auth::user()->type === 'employee')
                                    {{ __('You have not raised any grievances yet.') }}
                                @else
                                    {{ __('No grievances match your search criteria.') }}
                                @endif
                            </p>
                            @if (Auth::user()->type === 'employee')
                                <a href="{{ route('grievances.create') }}" class="btn btn-primary mt-2">
                                    <i class="ti ti-plus me-1"></i>
                                    {{ __('Raise Your First Grievance') }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
