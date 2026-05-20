@extends('layouts.admin')
@section('page-title') {{ __('Edit Survey') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('surveys.index') }}">{{ __('Surveys') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection

@push('css-page')
<style>
    .sv-form .form-label{font-weight:600;font-size:.84rem;color:#334155;}
    .sv-form .help{font-size:.72rem;color:#94a3b8;margin-top:3px;}
    .sv-type-card{cursor:pointer;border:2px solid #e2e8f0;border-radius:10px;padding:14px;text-align:center;transition:all .15s;background:#fff;}
    .sv-type-card:hover{border-color:#94a3b8;}
    .sv-type-card.active{border-color:#6366f1;background:linear-gradient(135deg,#eef2ff,#fff);box-shadow:0 4px 12px -4px rgba(99,102,241,.2);}
    .sv-type-card.disabled{opacity:.55;pointer-events:none;}
    .sv-type-card i{font-size:1.6rem;display:block;margin-bottom:6px;color:#6366f1;}
    .sv-type-card .ttl{font-size:.85rem;font-weight:700;color:#0f172a;}
    .sv-type-card .sub{font-size:.7rem;color:#64748b;margin-top:2px;}
    .dept-pick{max-height:180px;overflow:auto;border:1px solid #e2e8f0;border-radius:8px;padding:10px;background:#fafafa;}
</style>
@endpush

@section('content')
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @php
        $hasResponses = $survey->responses()->exists();
        $selectedDepts = old('department_ids', $survey->department_ids ?? []);
        if (!is_array($selectedDepts)) $selectedDepts = [];
    @endphp

    <form method="POST" action="{{ route('surveys.update', $survey->id) }}" class="sv-form">
        @csrf @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ti ti-pencil me-2"></i>{{ __('Edit Survey') }}</h5>
                        <a href="{{ route('surveys.questions', $survey->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="ti ti-list-check me-1"></i>{{ __('Manage Questions') }}
                            ({{ $survey->questions()->count() }})
                        </a>
                    </div>
                    <div class="card-body">

                        @if($hasResponses)
                            <div class="alert alert-warning small">
                                <i class="ti ti-alert-triangle me-1"></i>
                                {{ __('This survey already has responses. Type and Anonymous setting are locked to keep historical data consistent.') }}
                            </div>
                        @endif

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Survey Title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required maxlength="200"
                                   value="{{ old('title', $survey->title) }}">
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" class="form-control" rows="3" maxlength="2000">{{ old('description', $survey->description) }}</textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label d-block mb-2">{{ __('Survey Type') }} <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                @php $selectedType = old('type', $survey->type); @endphp
                                @foreach([
                                    'employee' => ['icon'=>'ti-clipboard-list',     'ttl'=>__('Employee Survey'), 'sub'=>__('General feedback')],
                                    'pulse'    => ['icon'=>'ti-activity-heartbeat', 'ttl'=>__('Pulse Survey'),    'sub'=>__('Short, recurring')],
                                    'enps'     => ['icon'=>'ti-trending-up',        'ttl'=>__('eNPS Survey'),     'sub'=>__('Recommend score')],
                                ] as $code => $meta)
                                    <div class="col-md-4">
                                        <label class="sv-type-card {{ $selectedType === $code ? 'active' : '' }} {{ $hasResponses ? 'disabled' : '' }}">
                                            <input type="radio" name="type" value="{{ $code }}" class="d-none js-type"
                                                   {{ $selectedType === $code ? 'checked' : '' }}
                                                   {{ $hasResponses ? 'disabled' : '' }}>
                                            <i class="ti {{ $meta['icon'] }}"></i>
                                            <div class="ttl">{{ $meta['ttl'] }}</div>
                                            <div class="sub">{{ $meta['sub'] }}</div>
                                        </label>
                                    </div>
                                @endforeach
                                @if($hasResponses)
                                    {{-- preserve type when disabled --}}
                                    <input type="hidden" name="type" value="{{ $survey->type }}">
                                @endif
                            </div>
                        </div>

                        <div class="form-group mb-3 js-pulse-freq" style="{{ $selectedType === 'pulse' ? '' : 'display:none;' }}">
                            <label class="form-label">{{ __('Pulse Frequency') }}</label>
                            <select name="frequency" class="form-control">
                                <option value="once"    {{ old('frequency', $survey->frequency) === 'once'    ? 'selected' : '' }}>{{ __('One-time') }}</option>
                                <option value="weekly"  {{ old('frequency', $survey->frequency) === 'weekly'  ? 'selected' : '' }}>{{ __('Weekly') }}</option>
                                <option value="monthly" {{ old('frequency', $survey->frequency) === 'monthly' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                                <option value="custom"  {{ old('frequency', $survey->frequency) === 'custom'  ? 'selected' : '' }}>{{ __('Custom') }}</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label">{{ __('Start Date') }}</label>
                                <input type="date" name="start_date" class="form-control"
                                       value="{{ old('start_date', optional($survey->start_date)->format('Y-m-d')) }}">
                            </div>
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label">{{ __('End Date') }}</label>
                                <input type="date" name="end_date" class="form-control"
                                       value="{{ old('end_date', optional($survey->end_date)->format('Y-m-d')) }}">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0"><i class="ti ti-settings me-2"></i>{{ __('Settings') }}</h5></div>
                    <div class="card-body">

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" class="form-control">
                                <option value="draft"  {{ old('status', $survey->status) === 'draft'  ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                <option value="active" {{ old('status', $survey->status) === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="closed" {{ old('status', $survey->status) === 'closed' ? 'selected' : '' }}>{{ __('Closed') }}</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_anonymous" value="0">
                                <input class="form-check-input" type="checkbox" id="anon" name="is_anonymous" value="1"
                                       {{ old('is_anonymous', $survey->is_anonymous) ? 'checked' : '' }}
                                       {{ $hasResponses ? 'disabled' : '' }}>
                                <label class="form-check-label" for="anon">
                                    <strong>{{ __('Anonymous Survey') }}</strong>
                                </label>
                            </div>
                            @if($hasResponses)
                                <input type="hidden" name="is_anonymous" value="{{ $survey->is_anonymous ? 1 : 0 }}">
                            @endif
                            <div class="help">{{ __('Hides identity in reports.') }}</div>
                        </div>

                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0"><i class="ti ti-building me-2"></i>{{ __('Audience') }}</h5></div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="audience_mode" id="aud-all" value="all"
                                   {{ empty($selectedDepts) ? 'checked' : '' }}>
                            <label class="form-check-label" for="aud-all"><strong>{{ __('All departments') }}</strong></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="audience_mode" id="aud-pick" value="pick"
                                   {{ !empty($selectedDepts) ? 'checked' : '' }}>
                            <label class="form-check-label" for="aud-pick">{{ __('Selected departments') }}</label>
                        </div>
                        <div class="dept-pick mt-2 js-dept-list" style="{{ !empty($selectedDepts) ? '' : 'display:none;' }}">
                            @forelse($departments as $d)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="department_ids[]"
                                           id="dept-{{ $d->id }}" value="{{ $d->id }}"
                                           {{ in_array($d->id, $selectedDepts) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="dept-{{ $d->id }}">{{ $d->name }}</label>
                                </div>
                            @empty
                                <small class="text-muted">{{ __('No departments yet.') }}</small>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('Save Changes') }}</button>
            <a href="{{ route('surveys.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection

@push('script-page')
<script>
(function(){
    document.querySelectorAll('.sv-type-card').forEach(function(card){
        card.addEventListener('click', function(){
            if (card.classList.contains('disabled')) return;
            document.querySelectorAll('.sv-type-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            const radio = card.querySelector('.js-type');
            if (radio) {
                radio.checked = true;
                document.querySelector('.js-pulse-freq').style.display = (radio.value === 'pulse') ? '' : 'none';
            }
        });
    });

    function syncDept(){
        const mode = document.querySelector('input[name="audience_mode"]:checked').value;
        const list = document.querySelector('.js-dept-list');
        if (mode === 'pick') {
            list.style.display = '';
        } else {
            list.style.display = 'none';
            list.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = false);
        }
    }
    document.querySelectorAll('input[name="audience_mode"]').forEach(r => r.addEventListener('change', syncDept));
})();
</script>
@endpush
