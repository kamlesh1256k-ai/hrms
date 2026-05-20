@extends('layouts.admin')

@section('page-title') {{ __('Manpower Requisitions') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item">{{ __('Requisitions') }}</li>
@endsection

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0"><i class="ti ti-file-plus me-1"></i>{{ __('Manpower Requisitions') }}</h6>
            <div class="d-flex gap-2">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Search title…') }}" class="form-control form-control-sm" style="width:200px;">
                    <select name="status" class="form-select form-select-sm" style="width:150px;" onchange="this.form.submit()">
                        <option value="">{{ __('All Statuses') }}</option>
                        @foreach(\App\Models\ManpowerRequisition::$statuses as $k => $label)
                            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-secondary"><i class="ti ti-search"></i></button>
                </form>
                <a href="{{ route('recruitment.requisitions.create') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i>{{ __('Raise Requisition') }}
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            @if($requisitions->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="ti ti-inbox" style="font-size:3rem;opacity:.4;"></i>
                    <p class="mt-2 mb-0">{{ __('No requisitions found.') }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Department') }}</th>
                                <th>{{ __('Positions') }}</th>
                                <th>{{ __('Priority') }}</th>
                                <th>{{ __('Reason') }}</th>
                                <th>{{ __('Raised By') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Job') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requisitions as $r)
                                <tr>
                                    <td class="text-muted">#{{ $r->id }}</td>
                                    <td><a href="{{ route('recruitment.requisitions.show', $r->id) }}" class="fw-semibold text-decoration-none">{{ $r->title }}</a></td>
                                    <td>{{ $r->department->name ?? '—' }}</td>
                                    <td>{{ $r->positions }}</td>
                                    <td>
                                        @php $pc = ['high'=>'danger','medium'=>'warning','low'=>'secondary'][$r->priority] ?? 'secondary'; @endphp
                                        <span class="badge bg-{{ $pc }} text-capitalize">{{ $r->priority }}</span>
                                    </td>
                                    <td class="text-capitalize">{{ str_replace('_',' ', $r->reason) }}</td>
                                    <td>{{ $r->raisedBy->name ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $r->status_badge }}">{{ \App\Models\ManpowerRequisition::$statuses[$r->status] ?? $r->status }}</span></td>
                                    <td>
                                        @if($r->job_id)
                                            <a href="{{ route('job.edit', $r->job_id) }}" class="text-decoration-none small">
                                                <i class="ti ti-link"></i> {{ __('Linked') }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('recruitment.requisitions.show', $r->id) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $requisitions->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
