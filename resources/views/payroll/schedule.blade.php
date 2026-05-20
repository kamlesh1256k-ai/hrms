@extends('layouts.admin')
@section('page-title')
    {{ __('Payroll - Pay Schedule') }}
@endsection

@section('content')
    @include('payroll._nav')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ __('Pay Schedule') }}</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('payroll.schedule.save') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('Pay Frequency') }}</label>
                        <input type="text" class="form-control" value="{{ strtoupper($schedule->pay_frequency) }}" readonly>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('Pay Day') }}</label>
                        <input type="number" name="pay_day" min="1" max="31" class="form-control" value="{{ old('pay_day', $schedule->pay_day) }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('Attendance Cycle Start Day') }}</label>
                        <input type="number" name="attendance_cycle_start_day" id="attCycleStartDay" min="1" max="28" class="form-control"
                               value="{{ old('attendance_cycle_start_day', $schedule->attendance_cycle_start_day ?? 1) }}">
                        <small class="text-muted d-block mt-1">
                            {{ __('1 = calendar month. e.g. 26 = 26th of prev month to 25th of current month.') }}
                        </small>
                        <div id="attCyclePreview" class="mt-2 p-2"
                             style="font-size:.82rem;border-radius:8px;background:#eef2ff;color:#3730a3;font-weight:600;display:none;">
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">{{ __('Start Month') }}</label>
                        <input type="month" name="start_month" class="form-control" value="{{ old('start_month', $schedule->start_month) }}" required>
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="status" id="status" value="1" {{ $schedule->status ? 'checked' : '' }}>
                            <label class="form-check-label" for="status">{{ __('Active') }}</label>
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label">{{ __('Working Days') }}</label>
                        @php($selected = explode(',', (string)$schedule->working_days))
                        <div class="d-flex flex-wrap gap-3">
                            @foreach(['mon' => 'Mon','tue' => 'Tue','wed' => 'Wed','thu' => 'Thu','fri' => 'Fri','sat' => 'Sat','sun' => 'Sun'] as $k => $v)
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input me-1" name="working_days[]" value="{{ $k }}" {{ in_array($k, $selected) ? 'checked' : '' }}>
                                    {{ $v }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Save Schedule') }}</button>
            </form>
        </div>
    </div>

    <script>
    (function () {
        var input   = document.getElementById('attCycleStartDay');
        var preview = document.getElementById('attCyclePreview');
        if (!input || !preview) return;

        var monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        function daysInMonth(year, monthIndex0) {
            return new Date(year, monthIndex0 + 1, 0).getDate();
        }

        function render() {
            var n = parseInt(input.value, 10);
            // Use the current month/year as the example payroll month
            var today = new Date();
            var curYear  = today.getFullYear();
            var curMon0  = today.getMonth(); // 0-based

            if (isNaN(n) || n <= 1) {
                var last = daysInMonth(curYear, curMon0);
                preview.style.display = 'block';
                preview.textContent =
                    'Calendar month — for ' + monthNames[curMon0] + ' ' + curYear +
                    ': 1 ' + monthNames[curMon0] + ' ' + curYear +
                    ' – ' + last + ' ' + monthNames[curMon0] + ' ' + curYear;
                return;
            }

            // Custom cycle: day N of previous month .. day (N-1) of current month
            var prevMon0 = curMon0 - 1;
            var prevYear = curYear;
            if (prevMon0 < 0) { prevMon0 = 11; prevYear = curYear - 1; }

            var startDay = Math.min(n, daysInMonth(prevYear, prevMon0));
            var endDay   = Math.min(n - 1, daysInMonth(curYear, curMon0));

            preview.style.display = 'block';
            preview.textContent =
                'Cycle for ' + monthNames[curMon0] + ' ' + curYear + ': ' +
                startDay + ' ' + monthNames[prevMon0] + ' ' + prevYear +
                ' – ' +
                endDay + ' ' + monthNames[curMon0] + ' ' + curYear;
        }

        input.addEventListener('input', render);
        input.addEventListener('change', render);
        render(); // show on page load
    })();
    </script>
@endsection

