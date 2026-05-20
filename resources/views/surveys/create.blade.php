@extends('layouts.admin')
@section('page-title') {{ __('Create Survey') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('surveys.index') }}">{{ __('Surveys') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@push('css-page')
<style>
    .sv-form .form-label{font-weight:600;font-size:.84rem;color:#334155;}
    .sv-form .help{font-size:.72rem;color:#94a3b8;margin-top:3px;}
    .sv-type-card{cursor:pointer;border:2px solid #e2e8f0;border-radius:10px;padding:14px;text-align:center;transition:all .15s;background:#fff;}
    .sv-type-card:hover{border-color:#94a3b8;}
    .sv-type-card.active{border-color:#6366f1;background:linear-gradient(135deg,#eef2ff,#fff);box-shadow:0 4px 12px -4px rgba(99,102,241,.2);}
    .sv-type-card i{font-size:1.6rem;display:block;margin-bottom:6px;color:#6366f1;}
    .sv-type-card .ttl{font-size:.85rem;font-weight:700;color:#0f172a;}
    .sv-type-card .sub{font-size:.7rem;color:#64748b;margin-top:2px;}
    .dept-pick{max-height:180px;overflow:auto;border:1px solid #e2e8f0;border-radius:8px;padding:10px;background:#fafafa;}
    .dept-pick .form-check{margin-bottom:4px;}

    /* Template chooser */
    .tpl-banner{
        background:linear-gradient(135deg,#eef2ff 0%,#fdf4ff 100%);
        border:1px solid #c7d2fe;
        border-radius:14px;padding:16px 18px;margin-bottom:16px;
    }
    .tpl-banner h6{margin:0 0 4px 0;font-weight:700;color:#3730a3;display:flex;align-items:center;gap:6px;font-size:.92rem;}
    .tpl-banner .help{font-size:.78rem;color:#6366f1;margin-bottom:10px;}
    .tpl-options{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px;}
    .tpl-option{display:flex;gap:10px;cursor:pointer;background:#fff;border:2px solid #e2e8f0;border-radius:10px;padding:10px 12px;transition:all .12s;}
    .tpl-option:hover{border-color:#a5b4fc;}
    .tpl-option.active{border-color:#6366f1;box-shadow:0 4px 12px -4px rgba(99,102,241,.25);background:#fafaff;}
    .tpl-option input{display:none;}
    .tpl-option .tpl-tick{width:20px;height:20px;border-radius:50%;border:2px solid #cbd5e1;flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;color:transparent;}
    .tpl-option.active .tpl-tick{background:#6366f1;border-color:#6366f1;color:#fff;}
    .tpl-option .tpl-tick i{font-size:.72rem;}
    .tpl-option .tpl-body{min-width:0;flex:1;}
    .tpl-option .tpl-ttl{font-weight:700;font-size:.84rem;color:#0f172a;margin:0;}
    .tpl-option .tpl-desc{font-size:.72rem;color:#64748b;margin-top:3px;line-height:1.35;}
    .tpl-info{margin-top:10px;padding:10px 12px;background:#fff;border:1px dashed #c7d2fe;border-radius:8px;font-size:.78rem;color:#475569;display:none;}
    .tpl-info.show{display:block;}
    .tpl-info strong{color:#3730a3;}
</style>
@endpush

@section('content')
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <form method="POST" action="{{ route('surveys.store') }}" class="sv-form">
        @csrf

        {{-- ── Template chooser (one-click setup) ─────────────────── --}}
        @if(!empty($templates))
        <div class="tpl-banner">
            <h6><i class="ti ti-template"></i>{{ __('Start from a template') }}</h6>
            <div class="help">{{ __('Pick a ready-made template — title, questions, and settings will be pre-filled. Choose "Blank" to build from scratch.') }}</div>

            <div class="tpl-options">
                {{-- Blank option --}}
                <label class="tpl-option {{ old('template') ? '' : 'active' }}">
                    <input type="radio" name="template" value="" class="js-tpl" {{ old('template') ? '' : 'checked' }}
                           data-type="" data-title="" data-desc="" data-anon="" data-freq=""
                           data-summary="">
                    <span class="tpl-tick"><i class="ti ti-check"></i></span>
                    <span class="tpl-body">
                        <div class="tpl-ttl">{{ __('Blank') }}</div>
                        <div class="tpl-desc">{{ __('Build your own from scratch.') }}</div>
                    </span>
                </label>

                @foreach($templates as $code => $tpl)
                    @php
                        $defs = \App\Services\SurveyTemplates::get($code);
                        $meta = $defs['meta'] ?? [];
                        $qcount = isset($defs['questions']) ? count($defs['questions']) : 0;
                    @endphp
                    <label class="tpl-option {{ old('template') === $code ? 'active' : '' }}">
                        <input type="radio" name="template" value="{{ $code }}" class="js-tpl"
                               {{ old('template') === $code ? 'checked' : '' }}
                               data-type="{{ $meta['type'] ?? '' }}"
                               data-title="{{ $meta['title'] ?? '' }}"
                               data-desc="{{ $meta['description'] ?? '' }}"
                               data-anon="{{ !empty($meta['is_anonymous']) ? 1 : 0 }}"
                               data-freq="{{ $meta['frequency'] ?? '' }}"
                               data-summary="{{ $qcount }} {{ __('questions') }} · {{ $tpl['description'] }}">
                        <span class="tpl-tick"><i class="ti ti-check"></i></span>
                        <span class="tpl-body">
                            <div class="tpl-ttl">{{ $tpl['label'] }}</div>
                            <div class="tpl-desc">{{ \Illuminate\Support\Str::limit($tpl['description'], 90) }}</div>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="tpl-info" id="tplInfo">
                <strong id="tplInfoTitle"></strong>
                <div id="tplInfoSummary" class="mt-1"></div>
            </div>
        </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0"><i class="ti ti-pencil me-2"></i>{{ __('Survey Details') }}</h5></div>
                    <div class="card-body">

                        {{-- Title --}}
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Survey Title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="svTitle" class="form-control" required maxlength="200"
                                   value="{{ old('title') }}" placeholder="{{ __('e.g. Q2 Engagement Pulse') }}">
                        </div>

                        {{-- Description --}}
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" id="svDescription" class="form-control" rows="3" maxlength="2000"
                                      placeholder="{{ __('Optional context shown to participants…') }}">{{ old('description') }}</textarea>
                            <div class="help">{{ __('Up to 2000 characters.') }}</div>
                        </div>

                        {{-- Type cards (radio) --}}
                        <div class="form-group mb-3">
                            <label class="form-label d-block mb-2">{{ __('Survey Type') }} <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                @php $selectedType = old('type', 'employee'); @endphp
                                @foreach([
                                    'employee' => ['icon' => 'ti-clipboard-list', 'ttl' => __('Employee Survey'), 'sub' => __('General feedback')],
                                    'pulse'    => ['icon' => 'ti-activity-heartbeat', 'ttl' => __('Pulse Survey'),    'sub' => __('Short, recurring')],
                                    'enps'     => ['icon' => 'ti-trending-up',  'ttl' => __('eNPS Survey'),     'sub' => __('Recommend score')],
                                ] as $code => $meta)
                                    <div class="col-md-4">
                                        <label class="sv-type-card {{ $selectedType === $code ? 'active' : '' }}">
                                            <input type="radio" name="type" value="{{ $code }}" class="d-none js-type"
                                                   {{ $selectedType === $code ? 'checked' : '' }}>
                                            <i class="ti {{ $meta['icon'] }}"></i>
                                            <div class="ttl">{{ $meta['ttl'] }}</div>
                                            <div class="sub">{{ $meta['sub'] }}</div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Pulse frequency (shown only for pulse) --}}
                        <div class="form-group mb-3 js-pulse-freq" style="display:none;">
                            <label class="form-label">{{ __('Pulse Frequency') }}</label>
                            <select name="frequency" class="form-control">
                                <option value="once"    {{ old('frequency', 'once') === 'once'    ? 'selected' : '' }}>{{ __('One-time') }}</option>
                                <option value="weekly"  {{ old('frequency') === 'weekly'  ? 'selected' : '' }}>{{ __('Weekly') }}</option>
                                <option value="monthly" {{ old('frequency') === 'monthly' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                                <option value="custom"  {{ old('frequency') === 'custom'  ? 'selected' : '' }}>{{ __('Custom') }}</option>
                            </select>
                            <div class="help">{{ __('How often this pulse should be sent. Recurring sends will be added in the scheduler phase.') }}</div>
                        </div>

                        {{-- Dates --}}
                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label">{{ __('Start Date') }}</label>
                                <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}">
                            </div>
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label">{{ __('End Date') }}</label>
                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- Status + Anon --}}
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0"><i class="ti ti-settings me-2"></i>{{ __('Settings') }}</h5></div>
                    <div class="card-body">

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Initial Status') }}</label>
                            <select name="status" class="form-control">
                                <option value="draft"  {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>{{ __('Active (publish now)') }}</option>
                            </select>
                            <div class="help">{{ __('You can activate later from the list.') }}</div>
                        </div>

                        <div class="form-group mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_anonymous" value="0">
                                <input class="form-check-input" type="checkbox" id="anon" name="is_anonymous" value="1"
                                       {{ old('is_anonymous') ? 'checked' : '' }}>
                                <label class="form-check-label" for="anon">
                                    <strong>{{ __('Anonymous Survey') }}</strong>
                                </label>
                            </div>
                            <div class="help">{{ __('Identity will not appear in reports — only aggregated counts.') }}</div>
                        </div>

                    </div>
                </div>

                {{-- Departments --}}
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0"><i class="ti ti-building me-2"></i>{{ __('Audience') }}</h5></div>
                    <div class="card-body">

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="audience_mode" id="aud-all" value="all" checked>
                            <label class="form-check-label" for="aud-all"><strong>{{ __('All departments') }}</strong></label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="audience_mode" id="aud-pick" value="pick"
                                   {{ old('department_ids') ? 'checked' : '' }}>
                            <label class="form-check-label" for="aud-pick">{{ __('Selected departments') }}</label>
                        </div>

                        <div class="dept-pick mt-2 js-dept-list" style="{{ old('department_ids') ? '' : 'display:none;' }}">
                            @forelse($departments as $d)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="department_ids[]"
                                           id="dept-{{ $d->id }}" value="{{ $d->id }}"
                                           {{ in_array($d->id, (array) old('department_ids', [])) ? 'checked' : '' }}>
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
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-check me-1"></i>{{ __('Create & Add Questions') }}
            </button>
            <a href="{{ route('surveys.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection

@push('script-page')
<script>
(function(){
    // Survey type card selection — visual active state
    document.querySelectorAll('.sv-type-card').forEach(function(card){
        card.addEventListener('click', function(){
            document.querySelectorAll('.sv-type-card').forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            const radio = card.querySelector('.js-type');
            if (radio) {
                radio.checked = true;
                togglePulseFreq(radio.value);
            }
        });
    });

    function togglePulseFreq(type){
        document.querySelector('.js-pulse-freq').style.display = (type === 'pulse') ? '' : 'none';
    }
    const initial = document.querySelector('.js-type:checked');
    if (initial) togglePulseFreq(initial.value);

    // Department picker — show/hide based on audience radio
    function syncDept(){
        const mode = document.querySelector('input[name="audience_mode"]:checked').value;
        const list = document.querySelector('.js-dept-list');
        if (mode === 'pick') {
            list.style.display = '';
        } else {
            list.style.display = 'none';
            // Uncheck all when switching back to "All"
            list.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = false);
        }
    }
    document.querySelectorAll('input[name="audience_mode"]').forEach(r => r.addEventListener('change', syncDept));

    // ── Template chooser ──
    // When a template is picked, pre-fill title, description, type-card,
    // anonymous toggle, and frequency. We do NOT overwrite values the
    // user has already typed in title/description (avoids surprise).
    const tplInputs = document.querySelectorAll('.js-tpl');
    const tplInfo   = document.getElementById('tplInfo');
    const titleEl   = document.getElementById('svTitle');
    const descEl    = document.getElementById('svDescription');
    const anonEl    = document.getElementById('anon');
    const freqEl    = document.querySelector('select[name="frequency"]');

    function applyTemplate(input){
        // Visual active state
        document.querySelectorAll('.tpl-option').forEach(o => o.classList.remove('active'));
        input.closest('.tpl-option').classList.add('active');

        const code   = input.value;
        const type   = input.dataset.type   || '';
        const title  = input.dataset.title  || '';
        const desc   = input.dataset.desc   || '';
        const anon   = input.dataset.anon === '1';
        const freq   = input.dataset.freq   || '';
        const summary = input.dataset.summary || '';

        // Info card (only when a template, not Blank)
        if (code && tplInfo) {
            tplInfo.classList.add('show');
            document.getElementById('tplInfoTitle').textContent = title || '';
            document.getElementById('tplInfoSummary').textContent = summary;
        } else if (tplInfo) {
            tplInfo.classList.remove('show');
        }

        if (!code) return; // Blank → don't auto-fill anything

        // Pre-fill title/desc if empty (don't clobber user's typing)
        if (titleEl && !titleEl.value.trim()) titleEl.value = title;
        if (descEl  && !descEl.value.trim())  descEl.value  = desc;

        // Activate the matching type card
        if (type) {
            const typeRadio = document.querySelector('.js-type[value="' + type + '"]');
            if (typeRadio) {
                document.querySelectorAll('.sv-type-card').forEach(c => c.classList.remove('active'));
                typeRadio.checked = true;
                typeRadio.closest('.sv-type-card').classList.add('active');
                togglePulseFreq(type);
            }
        }

        // Anonymous toggle
        if (anonEl) anonEl.checked = anon;

        // Frequency (pulse only)
        if (freqEl && freq) freqEl.value = freq;
    }

    tplInputs.forEach(inp => inp.addEventListener('change', () => applyTemplate(inp)));

    // On load: if a template radio is pre-selected (e.g. via old() after validation
    // failure), reflect it in the info card and active state.
    const preselected = Array.from(tplInputs).find(i => i.checked && i.value);
    if (preselected) applyTemplate(preselected);
})();
</script>
@endpush
