@php
    $setting = App\Models\Utility::settings();
    $plan = App\Models\Utility::getChatGPTSettings();
@endphp
{{ Form::open(['url' => 'interview-schedule', 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">

    @if ($plan->enable_chatgpt == 'on')
    <div class="text-end">
        <a href="#" class="btn btn-sm btn-primary" data-size="medium" data-ajax-popup-over="true" data-url="{{ route('generate', ['interview-schedule']) }}"
            data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('Generate') }}"
            data-title="{{ __('Generate Content With AI') }}">
            <i class="fas fa-robot"></i>{{ __(' Generate With AI') }}
        </a>
    </div>
    @endif

    <div class="row">
        <div class="form-group col-md-6">
            {{ Form::label('candidate', __('Interview To'), ['class' => 'col-form-label']) }}<x-required></x-required>
            {{ Form::select('candidate', $candidates, null, ['class' => 'form-control select2', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('employee', __('Interviewer'), ['class' => 'col-form-label']) }}<x-required></x-required>
            {{ Form::select('employee', $employees, null, ['class' => 'form-control select2', 'required' => 'required']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('date', __('Interview Date'), ['class' => 'col-form-label']) }}<x-required></x-required>
            {{ Form::text('date', null, ['class' => 'form-control d_week current_date', 'autocomplete' => 'off', 'id' => 'currentDate']) }}
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('time', __('Interview Time'), ['class' => 'col-form-label']) }}<x-required></x-required>
            {{ Form::time('time', null, ['class' => 'form-control ', 'id' => 'currentTime']) }}
        </div>

        {{-- Round type / mode --}}
        <div class="form-group col-md-6">
            {{ Form::label('round_type', __('Round Type'), ['class' => 'col-form-label']) }}<x-required></x-required>
            <select name="round_type" class="form-control select2" required>
                @foreach(\App\Models\InterviewSchedule::$roundTypes as $k => $label)
                    <option value="{{ $k }}" @selected($k === 'technical')>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('mode', __('Mode'), ['class' => 'col-form-label']) }}
            <select name="mode" class="form-control select2">
                @foreach(\App\Models\InterviewSchedule::$modes as $k => $label)
                    <option value="{{ $k }}" @selected($k === 'online')>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('round_label', __('Round Label (optional)'), ['class' => 'col-form-label']) }}
            {{ Form::text('round_label', null, ['class' => 'form-control', 'placeholder' => __('e.g. R2 - System Design'), 'maxlength' => 200]) }}
        </div>
        <div class="form-group col-md-12">
            {{ Form::label('meeting_link', __('Meeting Link (Zoom / Meet / Teams)'), ['class' => 'col-form-label']) }}
            {{ Form::url('meeting_link', null, ['class' => 'form-control', 'placeholder' => 'https://meet.google.com/abc-defg-hij', 'maxlength' => 500]) }}
        </div>

        <div class="form-group ">
            {{ Form::label('comment', __('Comment / Agenda'), ['class' => 'col-form-label']) }}
            {{ Form::textarea('comment', null, ['class' => 'form-control', 'rows' => '3', 'placeholder' => __('Topics to cover, prep instructions, etc.')]) }}
        </div>
        @if(isset($setting['is_enabled']) && $setting['is_enabled'] =='on')
        <div class="form-group col-md-6">
            {{ Form::label('synchronize_type', __('Synchroniz in Google Calendar ?'), ['class' => 'form-label']) }}
            <div class=" form-switch">
                <input type="checkbox" class="form-check-input mt-2" name="synchronize_type" id="switch-shadow"
                    value="google_calender">
                <label class="form-check-label" for="switch-shadow"></label>
            </div>
        </div>
        @endif
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn  btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">

</div>
{{ Form::close() }}

@if ($candidate != 0)
    <script>
        $('select#candidate').val({{ $candidate }}).trigger('change');
    </script>
@endif

<script>
    const getTwoDigits = (value) => value < 10 ? `0${value}` : value;

    const formatDate = (date) => {
        const day = getTwoDigits(date.getDate());
        const month = getTwoDigits(date.getMonth() + 1); // add 1 since getMonth returns 0-11 for the months
        const year = date.getFullYear();

        return `${year}-${month}-${day}`;
    }

    const formatTime = (date) => {
        const hours = getTwoDigits(date.getHours());
        const mins = getTwoDigits(date.getMinutes());

        return `${hours}:${mins}`;
    }

    const date = new Date();
    document.getElementById('currentDate').value = formatDate(date);
    document.getElementById('currentTime').value = formatTime(date);
</script>
