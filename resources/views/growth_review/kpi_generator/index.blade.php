@extends('layouts.admin')
@section('page-title') {{ __('KRA / KPI Generator') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item">{{ __('KRA / KPI Generator') }}</li>
@endsection

@push('css-page')
<style>
    .kg-card{border-radius:16px;box-shadow:0 4px 18px rgba(0,0,0,.04);}
    .kg-card .card-body{padding:32px;}
    .kg-title{font-weight:700;font-size:1.15rem;color:#1f2a44;margin-bottom:28px;}
    .kg-row{display:grid;grid-template-columns:1fr 1fr;gap:20px 40px;margin-bottom:14px;}
    .kg-field{display:grid;grid-template-columns:140px 1fr;align-items:center;gap:14px;}
    .kg-field label{font-weight:600;color:#1f2a44;text-align:right;margin:0;}
    .kg-field .form-control,.kg-field .form-select{border-radius:10px;border:1.5px solid #e2e5ec;height:42px;font-size:.92rem;}
    .kg-field .form-control:focus,.kg-field .form-select:focus{border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,.12);}
    .kg-actions{display:flex;gap:24px;justify-content:center;margin-top:30px;flex-wrap:wrap;}
    .kg-btn-primary{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:12px;padding:14px 40px;font-weight:600;min-width:280px;font-size:.95rem;}
    .kg-btn-primary:hover{background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;}
    .kg-btn-secondary{background:#fff;color:#64748b;border:1.5px solid #e2e5ec;border-radius:12px;padding:14px 40px;font-weight:600;min-width:280px;font-size:.95rem;}
    .kg-hint{text-align:center;color:#6d28d9;font-size:.78rem;margin-top:8px;font-weight:500;}
    .kg-hint-wrapper{display:flex;gap:24px;justify-content:center;flex-wrap:wrap;}
    .kg-hint-wrapper > div{min-width:280px;text-align:center;}
    @media(max-width:900px){.kg-row{grid-template-columns:1fr;gap:14px;}.kg-field{grid-template-columns:120px 1fr;}}
    .kg-recent{margin-top:26px;}
    .kg-recent table{font-size:.85rem;}
</style>
@endpush

@section('content')
    @include('growth_review._nav')

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <div class="card kg-card">
        <div class="card-body">
            <div class="kg-title">{{ __("Get Custom designed KRA / KPI's designed for each Job role in your Company within seconds.") }}</div>

            <form method="POST" action="{{ route('growth-review.kpi-generator.generate') }}" id="kgForm">
                @csrf
                <input type="hidden" name="output" id="kgOutput" value="view">
                <input type="hidden" name="ai_mode" id="kgAiMode" value="basic">

                <div class="kg-row">
                    <div class="kg-field"><label>{{ __('Performance Cycle') }}</label>
                        <select name="cycle_id" class="form-select">
                            <option value="">{{ __('Select Cycle') }}</option>
                            @foreach($cycles ?? [] as $cyc)<option value="{{ $cyc->id }}" {{ old('cycle_id')==$cyc->id?'selected':'' }}>{{ $cyc->name }} ({{ $cyc->start_date->format('M Y') }} - {{ $cyc->end_date->format('M Y') }})</option>@endforeach
                        </select>
                    </div>
                    <div class="kg-field"><label>{{ __('Job Role / Title') }}</label>
                        <input type="text" name="job_role" class="form-control" placeholder="Enter Job Role" required value="{{ old('job_role') }}">
                    </div>
                    <div class="kg-field"><label>{{ __('Department') }}</label>
                        <input type="text" name="department" class="form-control" placeholder="Enter Department" value="{{ old('department') }}">
                    </div>
                </div>

                <div class="kg-row">
                    <div class="kg-field"><label>{{ __('Company Size') }}</label>
                        <select name="company_size" class="form-select">
                            <option value="">Select</option>
                            @foreach($dropdowns['sizes'] as $s)<option value="{{ $s->name }}" {{ old('company_size')==$s->name?'selected':'' }}>{{ $s->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="kg-field"><label>{{ __('Industry') }}</label>
                        <select name="industry" class="form-select">
                            <option value="">Select Industry</option>
                            @foreach($dropdowns['industries'] as $i)<option value="{{ $i->name }}" {{ old('industry')==$i->name?'selected':'' }}>{{ $i->name }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div class="kg-row">
                    <div class="kg-field"><label>{{ __('City') }}</label>
                        <input type="text" name="city" class="form-control" placeholder="Enter City" value="{{ old('city') }}">
                    </div>
                    <div class="kg-field"><label>{{ __('Country') }}</label>
                        <select name="country" class="form-select">
                            <option value="">Select Country</option>
                            @foreach($countries as $ct)<option value="{{ $ct }}" {{ old('country')==$ct?'selected':'' }}>{{ $ct }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div class="kg-row">
                    <div class="kg-field"><label>{{ __('Seniority Level') }}</label>
                        <select name="seniority_level" class="form-select">
                            <option value="">Select Level</option>
                            @foreach($dropdowns['seniorities'] as $l)<option value="{{ $l->name }}" {{ old('seniority_level')==$l->name?'selected':'' }}>{{ $l->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="kg-field"><label>{{ __('Work Model') }}</label>
                        <select name="work_model" class="form-select">
                            <option value="">Select Work Model</option>
                            @foreach($dropdowns['workModels'] as $w)<option value="{{ $w->name }}" {{ old('work_model')==$w->name?'selected':'' }}>{{ $w->name }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div class="kg-row">
                    <div class="kg-field"><label>{{ __('Company Type') }}</label>
                        <select name="company_type" class="form-select">
                            <option value="">Select Company Type</option>
                            @foreach($dropdowns['compTypes'] as $t)<option value="{{ $t->name }}" {{ old('company_type')==$t->name?'selected':'' }}>{{ $t->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="kg-field"><label>{{ __('Target Timeframe') }}</label>
                        <select name="target_timeframe" class="form-select">
                            <option value="">Select Timeframe</option>
                            @foreach($dropdowns['timeframes'] as $tf)<option value="{{ $tf->name }}" {{ old('target_timeframe')==$tf->name?'selected':'' }}>{{ $tf->name }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div class="kg-row">
                    <div class="kg-field"><label>{{ __('No. of KRAs/KPIs') }}</label>
                        <input type="number" name="no_of_items" class="form-control" placeholder="e.g. 5" min="1" max="20" value="{{ old('no_of_items', 5) }}">
                    </div>
                    <div></div>
                </div>

                <div class="kg-actions">
                    <button type="submit" class="kg-btn-primary" onclick="document.getElementById('kgOutput').value='pdf';document.getElementById('kgAiMode').value='basic';">
                        <i class="ti ti-file-type-pdf me-2"></i>{{ __('Generate KRI/KPI PDF') }}
                    </button>
                    <button type="submit" class="kg-btn-secondary" onclick="document.getElementById('kgOutput').value='view';document.getElementById('kgAiMode').value='advanced';">
                        <i class="ti ti-file-text me-2"></i>{{ __('Generate KRI/KPI Word') }}
                    </button>
                </div>

                <div class="kg-hint-wrapper">
                    <div class="kg-hint">{{ __('Uses Basic AI Models') }}</div>
                    <div class="kg-hint">{{ __('Uses Advanced Thinking AI Models') }}</div>
                </div>
            </form>
        </div>
    </div>

    @if($recent->isNotEmpty())
    <div class="card kg-recent">
        <div class="card-body">
            <h6 class="mb-3"><i class="ti ti-history me-1"></i>{{ __('Recent Generations') }}</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr>
                        <th>{{ __('Cycle') }}</th>
                        <th>{{ __('Job Role') }}</th>
                        <th>{{ __('Industry') }}</th>
                        <th>{{ __('Seniority') }}</th>
                        <th>{{ __('Items') }}</th>
                        <th>{{ __('Assigned') }}</th>
                        <th>{{ __('Created') }}</th>
                        <th width="200">{{ __('Action') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($recent as $r)
                    @php $assignedN = (int) ($assignmentCounts[$r->id] ?? 0); @endphp
                    <tr>
                        <td><small>{{ $r->cycle->name ?? '—' }}</small></td>
                        <td><strong>{{ $r->job_role }}</strong>@if($r->department) <br><small class="text-muted">{{ $r->department }}</small>@endif</td>
                        <td>{{ $r->industry ?? '—' }}</td>
                        <td>{{ $r->seniority_level ?? '—' }}</td>
                        <td>{{ $r->no_of_items }}</td>
                        <td>
                            @if($assignedN > 0)
                                <span class="badge bg-success"><i class="ti ti-users me-1"></i>{{ $assignedN }}</span>
                            @else
                                <span class="text-muted" style="font-size:.75rem;">—</span>
                            @endif
                        </td>
                        <td><small>{{ $r->created_at->diffForHumans() }}</small></td>
                        <td>
                            <a href="{{ route('growth-review.kpi-generator.show', $r->id) }}" class="btn btn-sm btn-info" title="View"><i class="ti ti-eye"></i></a>
                            <button type="button" class="btn btn-sm btn-primary" title="Assign" data-bs-toggle="modal" data-bs-target="#assignModal{{ $r->id }}"><i class="ti ti-user-plus"></i></button>
                            <a href="{{ route('growth-review.kpi-generator.pdf', $r->id) }}" class="btn btn-sm btn-success" title="PDF"><i class="ti ti-file-type-pdf"></i></a>
                            <form action="{{ route('growth-review.kpi-generator.destroy', $r->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this record?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger"><i class="ti ti-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Assign Modals (one per recent row) --}}
    @foreach($recent as $r)
        @include('growth_review.kpi_generator._assign_modal', [
            'modalId'     => 'assignModal' . $r->id,
            'gen'         => $r,
            'employees'   => $employees,
            'assignedIds' => [],
        ])
    @endforeach
    @include('growth_review.kpi_generator._assign_modal_styles')
    @endif
@endsection
