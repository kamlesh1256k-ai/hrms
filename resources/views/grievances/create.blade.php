@extends('layouts.admin')

@section('page-title') {{ __('Raise Grievance') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('grievances.index') }}">{{ __('Grievances') }}</a></li>
    <li class="breadcrumb-item">{{ __('New') }}</li>
@endsection

@push('css-page')
<style>
    /* ── Page hero ───────────────────────────────────────────── */
    .gri-hero{
        background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 50%,#ec4899 100%);
        color:#fff;border-radius:14px;padding:22px 26px;margin-bottom:18px;
        box-shadow:0 10px 30px -10px rgba(124,58,237,.45);
        position:relative;overflow:hidden;
    }
    .gri-hero::before{
        content:'';position:absolute;right:-30px;top:-30px;width:160px;height:160px;
        background:rgba(255,255,255,.12);border-radius:50%;
    }
    .gri-hero::after{
        content:'';position:absolute;right:60px;bottom:-50px;width:120px;height:120px;
        background:rgba(255,255,255,.08);border-radius:50%;
    }
    .gri-hero h3{font-weight:700;margin:0 0 4px;letter-spacing:-.4px;position:relative;z-index:1;}
    .gri-hero p{margin:0;opacity:.92;font-size:.92rem;position:relative;z-index:1;}
    .gri-hero .hero-icon{
        width:54px;height:54px;background:rgba(255,255,255,.18);border-radius:14px;
        display:inline-flex;align-items:center;justify-content:center;font-size:1.7rem;
        margin-right:14px;backdrop-filter:blur(8px);position:relative;z-index:1;
    }

    /* ── Step pills ───────────────────────────────────────────── */
    .step-bar{display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;}
    .step-bar .step{
        flex:1;min-width:140px;background:#f1f5f9;border:1px solid #e2e8f0;
        border-radius:10px;padding:10px 14px;font-size:.78rem;font-weight:600;color:#94a3b8;
        display:flex;align-items:center;gap:8px;transition:.2s;
    }
    .step-bar .step .step-num{
        width:22px;height:22px;background:#cbd5e1;color:#fff;border-radius:50%;
        display:inline-flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;
    }
    .step-bar .step.done{background:#dcfce7;border-color:#86efac;color:#166534;}
    .step-bar .step.done .step-num{background:#10b981;}
    .step-bar .step.active{background:#eef2ff;border-color:#a5b4fc;color:#4338ca;box-shadow:0 4px 14px -8px rgba(99,102,241,.4);}
    .step-bar .step.active .step-num{background:#6366f1;}

    /* ── Anonymous toggle (premium) ────────────────────────── */
    .anon-card{
        border-radius:12px;padding:16px 18px;margin-bottom:18px;
        background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);
        border:1px solid #fbbf24;display:flex;align-items:center;gap:14px;
        transition:.25s;
    }
    .anon-card.is-on{
        background:linear-gradient(135deg,#dbeafe 0%,#bfdbfe 100%);
        border-color:#3b82f6;
    }
    .anon-card .anon-icon{
        width:46px;height:46px;border-radius:12px;background:rgba(255,255,255,.6);
        display:inline-flex;align-items:center;justify-content:center;font-size:1.3rem;color:#b45309;
        flex-shrink:0;
    }
    .anon-card.is-on .anon-icon{color:#1d4ed8;}
    .anon-card .form-check-input{transform:scale(1.25);margin-left:auto;cursor:pointer;}
    .anon-card .anon-title{font-weight:700;color:#92400e;margin:0;font-size:.95rem;}
    .anon-card.is-on .anon-title{color:#1e40af;}
    .anon-card .anon-help{font-size:.76rem;color:#78350f;margin:2px 0 0;}
    .anon-card.is-on .anon-help{color:#1e3a8a;}

    /* ── Category grid ────────────────────────────────────── */
    .cat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:6px;}
    .cat-tile{
        position:relative;border:2px solid #e2e8f0;border-radius:12px;padding:16px 14px;
        background:#fff;cursor:pointer;transition:.18s;text-align:center;
        display:flex;flex-direction:column;align-items:center;gap:10px;
    }
    .cat-tile:hover{border-color:#a5b4fc;background:#eef2ff;transform:translateY(-2px);box-shadow:0 8px 22px -10px rgba(99,102,241,.3);}
    .cat-tile.selected{
        border-color:#6366f1;background:linear-gradient(135deg,#eef2ff,#fff);
        box-shadow:0 8px 22px -8px rgba(99,102,241,.45);
    }
    .cat-tile.selected::after{
        content:'\2713';position:absolute;top:8px;right:8px;
        width:22px;height:22px;background:#6366f1;color:#fff;border-radius:50%;
        font-size:.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;
    }
    .cat-tile .cat-icon{
        width:46px;height:46px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;
        font-size:1.4rem;color:#fff;
    }
    .cat-tile .cat-lbl{font-weight:600;font-size:.84rem;color:#0f172a;}
    /* Per-category icon backgrounds */
    .cat-bg-hr        {background:linear-gradient(135deg,#3b82f6,#1d4ed8);}
    .cat-bg-salary    {background:linear-gradient(135deg,#10b981,#059669);}
    .cat-bg-manager   {background:linear-gradient(135deg,#8b5cf6,#7c3aed);}
    .cat-bg-harassment{background:linear-gradient(135deg,#ef4444,#b91c1c);}
    .cat-bg-work      {background:linear-gradient(135deg,#f59e0b,#d97706);}
    .cat-bg-policies  {background:linear-gradient(135deg,#6366f1,#4338ca);}
    .cat-bg-discrim   {background:linear-gradient(135deg,#ec4899,#be185d);}
    .cat-bg-other     {background:linear-gradient(135deg,#64748b,#475569);}

    /* ── Inputs ───────────────────────────────────────────── */
    .form-label.lbl-strong{font-weight:600;color:#1e293b;font-size:.88rem;margin-bottom:6px;}
    .form-control.gri-input{
        border:1.5px solid #e2e8f0;border-radius:10px;padding:11px 14px;font-size:.92rem;
        transition:.18s;background:#fff;
    }
    .form-control.gri-input:focus{
        border-color:#6366f1;box-shadow:0 0 0 4px rgba(99,102,241,.12);
    }
    textarea.gri-input{resize:vertical;min-height:160px;}
    .req-star{color:#ef4444;font-weight:700;}
    .help-line{font-size:.74rem;color:#94a3b8;margin-top:5px;}

    .meter{height:5px;border-radius:3px;background:#e2e8f0;overflow:hidden;margin-top:6px;}
    .meter > div{height:100%;background:linear-gradient(90deg,#ef4444,#f59e0b,#10b981);width:0;transition:width .25s;}

    /* ── Right info panel ───────────────────────────────── */
    .info-panel{
        position:sticky;top:80px;border:1px solid #e2e8f0;border-radius:12px;
        background:#fff;padding:18px;
    }
    .info-panel h6{font-weight:700;margin:0 0 12px;font-size:.85rem;color:#0f172a;text-transform:uppercase;letter-spacing:.4px;}
    .info-panel .info-row{display:flex;gap:10px;align-items:flex-start;padding:8px 0;border-bottom:1px dashed #e2e8f0;}
    .info-panel .info-row:last-child{border-bottom:0;}
    .info-panel .info-row i{font-size:1rem;color:#6366f1;margin-top:1px;flex-shrink:0;}
    .info-panel .info-row span{font-size:.78rem;color:#475569;line-height:1.5;}

    .conf-card{
        background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);
        border:1px solid #86efac;border-radius:10px;padding:12px 14px;margin-top:14px;
        font-size:.78rem;color:#166534;
    }
    .conf-card i{color:#15803d;}

    /* ── Submit area ───────────────────────────────── */
    .submit-bar{
        display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;
        padding:14px 0 0;border-top:1px solid #e2e8f0;margin-top:18px;
    }
    .btn-submit-primary{
        background:linear-gradient(135deg,#6366f1,#7c3aed);border:0;color:#fff;
        padding:11px 26px;font-weight:600;border-radius:10px;
        box-shadow:0 8px 22px -10px rgba(99,102,241,.6);
    }
    .btn-submit-primary:hover{filter:brightness(1.06);color:#fff;transform:translateY(-1px);}
    .btn-submit-primary:disabled{opacity:.6;cursor:not-allowed;transform:none;}
</style>
@endpush

@section('content')
    {{-- Hero --}}
    <div class="gri-hero">
        <div class="d-flex align-items-center">
            <div class="hero-icon"><i class="ti ti-message-circle-plus"></i></div>
            <div>
                <h3>{{ __('Raise a Grievance') }}</h3>
                <p>{{ __('Your voice matters — share concerns safely and confidentially.') }}</p>
            </div>
        </div>
    </div>

    {{-- Step indicator --}}
    <div class="step-bar">
        <div class="step" id="step1">
            <span class="step-num">1</span><span>{{ __('Choose Category') }}</span>
        </div>
        <div class="step" id="step2">
            <span class="step-num">2</span><span>{{ __('Add Details') }}</span>
        </div>
        <div class="step" id="step3">
            <span class="step-num">3</span><span>{{ __('Submit & Track') }}</span>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('grievances.store') }}" id="grievanceForm">
        @csrf
        <div class="row g-3">
            {{-- LEFT — main form --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">

                        {{-- Anonymous --}}
                        <div class="anon-card" id="anonCard">
                            <div class="anon-icon" id="anonIcon"><i class="ti ti-user-off"></i></div>
                            <div class="flex-grow-1">
                                <p class="anon-title" id="anonTitle">{{ __('Submit Identified') }}</p>
                                <p class="anon-help" id="anonHelp">{{ __('Your name will be visible to HR. Toggle on to submit anonymously.') }}</p>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="is_anonymous" id="is_anonymous" value="1" {{ old('is_anonymous') ? 'checked' : '' }}>
                            </div>
                        </div>

                        {{-- Category --}}
                        <div class="mb-4">
                            <label class="form-label lbl-strong">
                                <i class="ti ti-category me-1 text-primary"></i>
                                {{ __('Grievance Category') }} <span class="req-star">*</span>
                            </label>
                            <div class="help-line mb-2">{{ __('Pick the area that best describes your concern.') }}</div>

                            @php
                                $catMeta = [
                                    'HR'              => ['icon' => 'ti-users',         'bg' => 'cat-bg-hr'],
                                    'Salary'          => ['icon' => 'ti-coin',          'bg' => 'cat-bg-salary'],
                                    'Manager'         => ['icon' => 'ti-user-shield',   'bg' => 'cat-bg-manager'],
                                    'Harassment'      => ['icon' => 'ti-shield-x',      'bg' => 'cat-bg-harassment'],
                                    'Work Conditions' => ['icon' => 'ti-building',      'bg' => 'cat-bg-work'],
                                    'Policies'        => ['icon' => 'ti-file-text',     'bg' => 'cat-bg-policies'],
                                    'Discrimination'  => ['icon' => 'ti-alert-triangle','bg' => 'cat-bg-discrim'],
                                ];
                            @endphp

                            <div class="cat-grid">
                                @foreach (\App\Models\Grievance::getCategories() as $code => $label)
                                    @php
                                        $meta = $catMeta[$code] ?? ['icon' => 'ti-help', 'bg' => 'cat-bg-other'];
                                        $isSel = old('category') === $code;
                                    @endphp
                                    <div class="cat-tile {{ $isSel ? 'selected' : '' }}" data-cat="{{ $code }}">
                                        <span class="cat-icon {{ $meta['bg'] }}"><i class="ti {{ $meta['icon'] }}"></i></span>
                                        <span class="cat-lbl">{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <input type="hidden" name="category" id="selected_category" value="{{ old('category') }}" required>
                        </div>

                        {{-- Title --}}
                        <div class="mb-4">
                            <label for="title" class="form-label lbl-strong">
                                <i class="ti ti-pencil me-1 text-primary"></i>
                                {{ __('Grievance Title') }} <span class="req-star">*</span>
                            </label>
                            <input type="text" class="form-control gri-input" id="title" name="title"
                                   value="{{ old('title') }}" required maxlength="255"
                                   placeholder="{{ __('e.g. Delay in salary credit for March') }}">
                            <div class="help-line">{{ __('Keep it short and specific — 5 to 12 words is ideal.') }}</div>
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label for="description" class="form-label lbl-strong">
                                <i class="ti ti-align-left me-1 text-primary"></i>
                                {{ __('Detailed Description') }} <span class="req-star">*</span>
                            </label>
                            <textarea class="form-control gri-input" id="description" name="description"
                                      rows="8" required minlength="20"
                                      placeholder="{{ __('Describe what happened, when it happened, and who was involved. Include any specific dates or incidents…') }}">{{ old('description') }}</textarea>
                            <div class="meter"><div id="lenMeter"></div></div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="help-line m-0" id="lenLabel">{{ __('Minimum 20 characters') }}</small>
                                <small class="help-line m-0" id="charCount">0 / 2000</small>
                            </div>
                        </div>

                        <div class="submit-bar">
                            <a href="{{ route('grievances.index') }}" class="btn btn-light border">
                                <i class="ti ti-arrow-left me-1"></i>{{ __('Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-submit-primary" id="submitBtn">
                                <i class="ti ti-send me-1"></i>{{ __('Submit Grievance') }}
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            {{-- RIGHT — info / tips panel --}}
            <div class="col-lg-4">
                <div class="info-panel">
                    <h6><i class="ti ti-info-circle me-1 text-primary"></i>{{ __('Before You Submit') }}</h6>
                    <div class="info-row">
                        <i class="ti ti-shield-check"></i>
                        <span>{{ __('All grievances are reviewed by HR with strict confidentiality.') }}</span>
                    </div>
                    <div class="info-row">
                        <i class="ti ti-clock"></i>
                        <span>{{ __('You will receive status updates as your case progresses.') }}</span>
                    </div>
                    <div class="info-row">
                        <i class="ti ti-eye"></i>
                        <span>{{ __('Anonymous submissions hide your identity and provide a tracking token.') }}</span>
                    </div>
                    <div class="info-row">
                        <i class="ti ti-alert-triangle text-warning"></i>
                        <span>{{ __('False or malicious grievances may result in disciplinary action.') }}</span>
                    </div>

                    <div class="conf-card">
                        <i class="ti ti-lock me-1"></i>
                        <strong>{{ __('Confidential.') }}</strong>
                        {{ __('Your grievance is encrypted in transit and access is restricted to authorised HR personnel.') }}
                    </div>

                    <h6 class="mt-4"><i class="ti ti-bulb me-1 text-warning"></i>{{ __('Writing Tips') }}</h6>
                    <div class="info-row"><i class="ti ti-point"></i><span>{{ __('Stick to facts — what, when, where.') }}</span></div>
                    <div class="info-row"><i class="ti ti-point"></i><span>{{ __('Mention specific dates or incidents.') }}</span></div>
                    <div class="info-row"><i class="ti ti-point"></i><span>{{ __('Be concise and avoid speculation.') }}</span></div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('script-page')
<script>
(function(){
    const tiles  = document.querySelectorAll('.cat-tile');
    const hidden = document.getElementById('selected_category');
    const title  = document.getElementById('title');
    const desc   = document.getElementById('description');
    const charC  = document.getElementById('charCount');
    const lenMt  = document.getElementById('lenMeter');
    const lenLb  = document.getElementById('lenLabel');
    const anon   = document.getElementById('is_anonymous');
    const aCard  = document.getElementById('anonCard');
    const aIcon  = document.getElementById('anonIcon');
    const aTitle = document.getElementById('anonTitle');
    const aHelp  = document.getElementById('anonHelp');
    const s1 = document.getElementById('step1'), s2 = document.getElementById('step2'), s3 = document.getElementById('step3');
    const submitBtn = document.getElementById('submitBtn');
    const MAX = 2000;

    /* Category tiles */
    tiles.forEach(t => t.addEventListener('click', () => {
        tiles.forEach(x => x.classList.remove('selected'));
        t.classList.add('selected');
        hidden.value = t.dataset.cat;
        recalcSteps();
    }));

    /* Description meter & counter */
    function refreshLen(){
        let v = desc.value;
        if (v.length > MAX) { desc.value = v = v.substring(0, MAX); }
        const len = v.length;
        charC.textContent = len + ' / ' + MAX;
        const pct = Math.min(100, Math.round((len / 200) * 100));    // visually full at ~200 chars
        lenMt.style.width = pct + '%';
        if (len < 20) {
            lenLb.textContent = `{{ __('Minimum 20 characters') }} (${20 - len} more)`;
            lenLb.style.color = '#ef4444';
        } else {
            lenLb.textContent = '{{ __('Looking good — add more detail if you can') }}';
            lenLb.style.color = '#10b981';
        }
        recalcSteps();
    }
    desc.addEventListener('input', refreshLen);
    title.addEventListener('input', recalcSteps);

    /* Step indicator state */
    function recalcSteps(){
        const hasCat   = !!hidden.value;
        const hasTitle = title.value.trim().length > 0;
        const hasDesc  = desc.value.trim().length >= 20;

        [s1, s2, s3].forEach(s => s.classList.remove('done', 'active'));
        if (hasCat) s1.classList.add('done'); else s1.classList.add('active');
        if (hasCat && hasTitle && hasDesc) s2.classList.add('done');
        else if (hasCat) s2.classList.add('active');
        if (hasCat && hasTitle && hasDesc) s3.classList.add('active');
    }

    /* Anonymous toggle UI */
    function refreshAnon(){
        if (anon.checked) {
            aCard.classList.add('is-on');
            aIcon.innerHTML = '<i class="ti ti-user-off"></i>';
            aTitle.textContent = '{{ __('Submitting Anonymously') }}';
            aHelp.textContent  = '{{ __('Your identity will be hidden. Save the tracking token after submission to follow up.') }}';
        } else {
            aCard.classList.remove('is-on');
            aIcon.innerHTML = '<i class="ti ti-user"></i>';
            aTitle.textContent = '{{ __('Submit Identified') }}';
            aHelp.textContent  = '{{ __('Your name will be visible to HR. Toggle on to submit anonymously.') }}';
        }
    }
    anon.addEventListener('change', function(){
        if (this.checked) {
            if (!confirm('{{ __("Submit anonymously? Save the tracking token afterwards — you'll need it to follow up.") }}')) {
                this.checked = false;
            }
        }
        refreshAnon();
    });

    /* Form validation */
    document.getElementById('grievanceForm').addEventListener('submit', function(e){
        const cat   = hidden.value;
        const t     = title.value.trim();
        const d     = desc.value.trim();
        if (!cat) { e.preventDefault(); alert('{{ __("Please choose a grievance category.") }}'); return; }
        if (!t) { e.preventDefault(); title.focus(); alert('{{ __("Please add a title.") }}'); return; }
        if (d.length < 20) { e.preventDefault(); desc.focus(); alert('{{ __("Description must be at least 20 characters.") }}'); return; }
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ti ti-loader-2 me-1"></i>{{ __('Submitting…') }}';
    });

    refreshLen();
    refreshAnon();
    recalcSteps();
})();
</script>
@endpush
