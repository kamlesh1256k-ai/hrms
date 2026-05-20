@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Timesheet') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Timesheet') }}</li>
@endsection

@section('action-button')
    <a href="#" data-url="{{ route('timesheet.file.import') }}" data-ajax-popup="true"
        data-title="{{ __('Import Timesheet CSV file') }}" data-bs-toggle="tooltip" title=""
        class="btn btn-sm btn-primary me-1" data-bs-original-title="{{ __('Import') }}">
        <i class="ti ti-file-import"></i>
    </a>

    <a href="{{ route('timesheet.export') }}" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip"
        data-bs-original-title="{{ __('Export') }}">
        <i class="ti ti-file-export"></i>
    </a>

@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="mt-2" id="multiCollapseExample1">
                <div class="card">
                    <div class="card-body">
                        {{ Form::open(['route' => ['timesheet.index'], 'method' => 'get', 'id' => 'timesheet_filter']) }}
                        <div class="row align-items-center justify-content-end">
                            <div class="col-xl-10">
                                <div class="row">
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box"></div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('start_date', __('Start Date'), ['class' => 'form-label']) }}
                                            {{ Form::date('start_date', isset($_GET['start_date']) ? $_GET['start_date'] : '', ['class' => 'month-btn form-control current_date', 'autocomplete' => 'off', 'id' => 'current_date']) }}
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            {{ Form::label('end_date', __('End Date'), ['class' => 'form-label']) }}
                                            {{ Form::date('end_date', isset($_GET['end_date']) ? $_GET['end_date'] : '', ['class' => 'month-btn form-control current_date', 'autocomplete' => 'off', 'id' => 'current_date']) }}
                                        </div>
                                    </div>
                                    <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 col-12">
                                        <div class="btn-box">
                                            @if (\Auth::user()->type != 'employee' || ($isManager ?? false))
                                                {{ Form::label('employee', __('Employee'), ['class' => 'form-label']) }}
                                                {{ Form::select('employee', $employeesList, isset($_GET['employee']) ? $_GET['employee'] : '', ['class' => 'form-control select ', 'id' => 'employee_id']) }}
                                            @else
                                                {!! Form::hidden('employee', \Auth::user()->id, ['id' => 'employee_id']) !!}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="row">
                                    <div class="col-auto mt-4">
                                        <a href="#" class="btn btn-sm btn-primary me-1"
                                            onclick="document.getElementById('timesheet_filter').submit(); return false;"
                                            data-bs-toggle="tooltip" title="" data-bs-original-title="apply">
                                            <span class="btn-inner--icon"><i class="ti ti-search"></i></span>
                                        </a>
                                        <a href="{{ route('timesheet.index') }}" class="btn btn-sm btn-danger me-1"
                                            data-bs-toggle="tooltip" title="" data-bs-original-title="Reset">
                                            <span class="btn-inner--icon"><i
                                                    class="ti ti-refresh text-white-off "></i></span>
                                        </a>
                                        <a href="{{ route('timesheet.export.excel', ['start_date' => isset($_GET['start_date']) ? $_GET['start_date'] : '', 'end_date' => isset($_GET['end_date']) ? $_GET['end_date'] : '', 'employee' => isset($_GET['employee']) ? $_GET['employee'] : '']) }}" class="btn btn-sm btn-success">
                                            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('Excel') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Inline Create Form --}}
    @canany(['Create TimeSheet', 'Manage TimeSheet'])
    <div class="card mb-3" id="tsAddCard">
        <div class="card-header py-3"><h6 class="mb-0"><i class="ti ti-plus me-1"></i>{{ __('Add Timesheet Entry') }}</h6></div>
        <div class="card-body py-3">
            {{ Form::open(['route' => ['timesheet.store'], 'class' => 'needs-validation', 'novalidate']) }}
            <div class="row g-2 align-items-end">
                @if (\Auth::user()->type != 'employee' || ($isManager ?? false))
                <div class="col-md-2">
                    {{ Form::label('employee_id', __('Employee'), ['class' => 'form-label mb-1']) }}
                    {!! Form::select('employee_id', $employeesList, null, ['class' => 'form-control form-control-sm', 'required' => 'required']) !!}
                </div>
                @endif
                <div class="col-md-2">
                    {{ Form::label('client_name', __('Client Name'), ['class' => 'form-label mb-1']) }}
                    {{ Form::text('client_name', '', ['class' => 'form-control form-control-sm', 'placeholder' => __('Client')]) }}
                </div>
                <div class="col-md-2">
                    {{ Form::label('task', __('Task / Work'), ['class' => 'form-label mb-1']) }}
                    {{ Form::text('task', '', ['class' => 'form-control form-control-sm', 'placeholder' => __('Task'), 'required' => 'required']) }}
                </div>
                <div class="col-md-1">
                    {{ Form::label('category', __('Category'), ['class' => 'form-label mb-1']) }}
                    {{ Form::select('category', ['' => __('Select'), 'Taxation' => 'Taxation', 'Audit' => 'Audit', 'Compliance' => 'Compliance', 'Admin' => 'Admin', 'Other' => 'Other'], null, ['class' => 'form-control form-control-sm']) }}
                </div>
                <div class="col-md-1">
                    {{ Form::label('date', __('Date'), ['class' => 'form-label mb-1']) }}
                    {{ Form::date('date', date('Y-m-d'), ['class' => 'form-control form-control-sm', 'required' => 'required']) }}
                </div>
                <div class="col-md-1">
                    {{ Form::label('start_time', __('Start'), ['class' => 'form-label mb-1']) }}
                    {{ Form::time('start_time', '', ['class' => 'form-control form-control-sm']) }}
                </div>
                <div class="col-md-1">
                    {{ Form::label('end_time', __('End'), ['class' => 'form-label mb-1']) }}
                    {{ Form::time('end_time', '', ['class' => 'form-control form-control-sm']) }}
                </div>
                <div class="col-md-1">
                    {{ Form::label('hours', __('Hours'), ['class' => 'form-label mb-1']) }}
                    {{ Form::number('hours', '', ['class' => 'form-control form-control-sm', 'required' => 'required', 'step' => '0.01', 'placeholder' => '0.00', 'id' => 'ts_hours']) }}
                </div>
                <div class="col-md-1">
                    {{ Form::label('billable', __('Billable'), ['class' => 'form-label mb-1']) }}
                    {{ Form::select('billable', ['billable' => __('Billable'), 'non-billable' => __('Non-Billable')], null, ['class' => 'form-control form-control-sm']) }}
                </div>
            </div>
            <div class="row g-2 align-items-end mt-1">
                <div class="col-md-1">
                    {{ Form::label('status', __('Status'), ['class' => 'form-label mb-1']) }}
                    {{ Form::select('status', ['pending' => __('Pending'), 'in_progress' => __('In Progress'), 'completed' => __('Completed')], null, ['class' => 'form-control form-control-sm']) }}
                </div>
                <div class="col-md-3">
                    {{ Form::label('remark', __('Remark'), ['class' => 'form-label mb-1']) }}
                    {{ Form::text('remark', '', ['class' => 'form-control form-control-sm', 'placeholder' => __('Enter remark')]) }}
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="ti ti-check me-1"></i>{{ __('Add') }}</button>
                </div>
            </div>
            {{ Form::close() }}
        </div>
    </div>
    @endcanany

    <div class="col-xl-12">
        <div class="card">
            <div class="card-header card-body table-border-style">
                <div class="card-body py-0">

                    <div class="table-responsive">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    @if (\Auth::user()->type != 'employee' || ($isManager ?? false))
                                        <th>{{ __('Employee') }}</th>
                                    @endif
                                    <th>{{ __('Client Name') }}</th>
                                    <th>{{ __('Task / Work Done') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Start Time') }}</th>
                                    <th>{{ __('End Time') }}</th>
                                    <th>{{ __('Hours') }}</th>
                                    <th>{{ __('Billable') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th width="120px">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($timeSheets as $timeSheet)
                                    <tr>
                                        <td>{{ \Auth::user()->dateFormat($timeSheet->date) }}</td>
                                        @if (\Auth::user()->type != 'employee' || ($isManager ?? false))
                                            <td>{{ !empty($timeSheet->employee) ? $timeSheet->employee->name : '' }}</td>
                                        @endif
                                        <td>{{ $timeSheet->client_name ?? '—' }}</td>
                                        <td>{{ $timeSheet->task ?? '—' }}</td>
                                        <td>{{ $timeSheet->category ?? '—' }}</td>
                                        <td>{{ $timeSheet->start_time ? date('h:i A', strtotime($timeSheet->start_time)) : '—' }}</td>
                                        <td>{{ $timeSheet->end_time ? date('h:i A', strtotime($timeSheet->end_time)) : '—' }}</td>
                                        <td>{{ $timeSheet->hours }}</td>
                                        <td>
                                            @if($timeSheet->billable === 'billable')
                                                <span class="badge bg-success">{{ __('Billable') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Non-Billable') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($timeSheet->status === 'completed')
                                                <span class="badge bg-success">{{ __('Completed') }}</span>
                                            @elseif($timeSheet->status === 'in_progress')
                                                <span class="badge bg-info">{{ __('In Progress') }}</span>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                        <td class="Action">
                                            <div class="d-flex gap-1">
                                                {{-- Continue: add more time to same task on new day --}}
                                                @if(in_array($timeSheet->status, ['pending', 'in_progress']))
                                                    <a href="#" class="btn btn-sm btn-outline-success btn-continue-task"
                                                        data-client="{{ $timeSheet->client_name }}"
                                                        data-task="{{ $timeSheet->task }}"
                                                        data-category="{{ $timeSheet->category }}"
                                                        data-billable="{{ $timeSheet->billable }}"
                                                        data-bs-toggle="tooltip" title="{{ __('Continue this task') }}">
                                                        <i class="ti ti-player-play"></i>
                                                    </a>
                                                @endif

                                                @can('Edit TimeSheet')
                                                    <a href="#" class="btn btn-sm btn-outline-info"
                                                        data-url="{{ route('timesheet.edit', $timeSheet->id) }}"
                                                        data-ajax-popup="true" data-size="md"
                                                        data-title="{{ __('Edit Timesheet') }}"
                                                        data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                        <i class="ti ti-pencil"></i>
                                                    </a>
                                                @endcan

                                                @can('Delete TimeSheet')
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['timesheet.destroy', $timeSheet->id], 'id' => 'delete-form-' . $timeSheet->id, 'class' => 'd-inline']) !!}
                                                    <a href="#" class="btn btn-sm btn-outline-danger bs-pass-para"
                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        $(document).ready(function() {
            var now = new Date();
            var month = (now.getMonth() + 1);
            var day = now.getDate();
            if (month < 10) month = "0" + month;
            if (day < 10) day = "0" + day;
            var today = now.getFullYear() + '-' + month + '-' + day;
            $('.current_date').val(today);
        });
    </script>
    <script>
        // Auto-calculate hours from start/end time
        var st = document.querySelector('input[name="start_time"]');
        var et = document.querySelector('input[name="end_time"]');
        if (st && et) {
            function calcHours() {
                if (st.value && et.value) {
                    var s = st.value.split(':'), e = et.value.split(':');
                    var diff = (parseInt(e[0])*60 + parseInt(e[1])) - (parseInt(s[0])*60 + parseInt(s[1]));
                    if (diff > 0) document.getElementById('ts_hours').value = (diff / 60).toFixed(2);
                }
            }
            st.addEventListener('change', calcHours);
            et.addEventListener('change', calcHours);
        }
    </script>
    <script>
        // Continue task: pre-fill inline form with task details from a pending entry
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.btn-continue-task');
            if (!btn) return;
            e.preventDefault();

            var card = document.getElementById('tsAddCard');
            if (!card) { alert('Add Timesheet form not found.'); return; }
            var form = card.querySelector('form');
            if (!form) return;

            var client = form.querySelector('input[name="client_name"]');
            var task = form.querySelector('input[name="task"]');
            var cat = form.querySelector('select[name="category"]');
            var billable = form.querySelector('select[name="billable"]');
            var status = form.querySelector('select[name="status"]');

            if (client) client.value = btn.dataset.client || '';
            if (task) task.value = btn.dataset.task || '';
            if (cat) cat.value = btn.dataset.category || '';
            if (billable) billable.value = btn.dataset.billable || 'billable';
            if (status) status.value = 'in_progress';

            // Scroll to form and highlight
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            form.closest('.card').style.boxShadow = '0 0 0 3px rgba(16,185,129,.4)';
            setTimeout(function() {
                form.closest('.card').style.boxShadow = '';
            }, 2000);

            // Focus on start time
            var st = form.querySelector('input[name="start_time"]');
            if (st) setTimeout(function() { st.focus(); }, 500);
        });
    </script>
@endpush
