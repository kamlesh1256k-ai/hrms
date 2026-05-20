@extends('layouts.admin')
@section('page-title') {{ __('Survey Questions') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('surveys.index') }}">{{ __('Surveys') }}</a></li>
    <li class="breadcrumb-item">{{ __('Questions') }}</li>
@endsection

@push('css-page')
<style>
    .qb-card{border:1px solid #e2e8f0;border-radius:12px;background:#fff;padding:14px 16px;margin-bottom:12px;transition:box-shadow .12s;}
    .qb-card:hover{box-shadow:0 6px 16px -8px rgba(15,23,42,.12);}
    .qb-card.is-enps{border-left:4px solid #6d28d9;background:linear-gradient(135deg,#faf5ff 0%,#fff 60%);}
    .qb-handle{cursor:grab;color:#94a3b8;}
    .qb-handle:active{cursor:grabbing;}
    .qb-num{display:inline-flex;width:28px;height:28px;border-radius:50%;background:#eef2ff;color:#4338ca;font-weight:700;font-size:.78rem;align-items:center;justify-content:center;margin-right:8px;}
    .qb-type-badge{font-size:.65rem;padding:2px 8px;border-radius:6px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;}
    .qt-rating_5      {background:#dbeafe;color:#1d4ed8;}
    .qt-rating_10     {background:#ede9fe;color:#6d28d9;}
    .qt-yes_no        {background:#dcfce7;color:#15803d;}
    .qt-multiple_choice{background:#fef3c7;color:#b45309;}
    .qt-text          {background:#fce7f3;color:#be185d;}
    .qb-meta{font-size:.72rem;color:#64748b;margin-top:6px;}
    .qb-options{font-size:.78rem;color:#475569;margin-top:6px;padding-left:18px;}
    .qb-empty{text-align:center;padding:40px 16px;color:#94a3b8;border:2px dashed #e2e8f0;border-radius:12px;background:#fafafa;}
    .qb-empty i{font-size:2.4rem;opacity:.4;display:block;margin-bottom:10px;}
    .opts-list .opt-row{display:flex;gap:6px;align-items:center;margin-bottom:6px;}
    .opts-list .opt-row input{flex:1;}
    .qb-locked-banner{background:#fef9c3;border:1px solid #facc15;color:#854d0e;border-radius:10px;padding:10px 14px;font-size:.84rem;}
</style>
@endpush

@section('content')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    @php
        $hasResponses = $survey->responses()->exists();
        $isLocked = $hasResponses;     // schema-changing edits blocked
    @endphp

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1"><i class="ti ti-list-check me-2"></i>{{ $survey->title }}</h5>
                        <small class="text-muted">
                            <span class="badge bg-light text-dark">{{ ucfirst($survey->type) }}</span>
                            <span class="badge bg-light text-dark ms-1">{{ ucfirst($survey->status) }}</span>
                            @if($survey->is_anonymous)<span class="badge" style="background:#ede9fe;color:#6d28d9;"><i class="ti ti-eye-off me-1"></i>{{ __('Anonymous') }}</span>@endif
                            <span class="ms-2">{{ $survey->questions->count() }} {{ __('questions') }}</span>
                        </small>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="{{ route('surveys.edit', $survey->id) }}" class="btn btn-light btn-sm border"><i class="ti ti-pencil me-1"></i>{{ __('Edit Settings') }}</a>
                        <a href="{{ route('surveys.index') }}" class="btn btn-light btn-sm border">{{ __('Done') }}</a>
                    </div>
                </div>
                <div class="card-body">
                    @if($isLocked)
                        <div class="qb-locked-banner mb-3">
                            <i class="ti ti-lock me-1"></i>
                            {{ __('Questions are locked because responses have already been submitted. To change the structure, close & duplicate this survey.') }}
                        </div>
                    @endif

                    @forelse($survey->questions as $i => $q)
                        <div class="qb-card {{ $q->is_enps ? 'is-enps' : '' }}" data-qid="{{ $q->id }}">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="flex-grow-1">
                                    <span class="qb-num">{{ $i + 1 }}</span>
                                    <strong>{{ $q->question_text }}</strong>
                                    @if($q->is_required)<span class="text-danger">*</span>@endif
                                </div>
                                <div class="d-flex gap-1 align-items-center">
                                    <span class="qb-type-badge qt-{{ $q->question_type }}">
                                        {{ [
                                            'rating_5' => __('Rating 1-5'),
                                            'rating_10' => __('Rating 0-10'),
                                            'yes_no' => __('Yes/No'),
                                            'multiple_choice' => __('Choice'),
                                            'text' => __('Text'),
                                        ][$q->question_type] ?? $q->question_type }}
                                    </span>
                                    @if($q->is_enps)<span class="qb-type-badge" style="background:#6d28d9;color:#fff;">eNPS</span>@endif
                                    @if(!$isLocked)
                                        <i class="ti ti-grip-vertical qb-handle ms-1" title="{{ __('Drag to reorder') }}"></i>
                                        <form method="POST" action="{{ route('surveys.questions.destroy', [$survey->id, $q->id]) }}" onsubmit="return confirm('{{ __('Delete this question?') }}')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-light border text-danger" title="{{ __('Delete') }}"><i class="ti ti-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div class="qb-meta">
                                {{ $q->is_required ? __('Required') : __('Optional') }}
                                @if($q->question_type === 'multiple_choice' && is_array($q->options))
                                    · {{ count($q->options) }} {{ __('options') }}
                                @endif
                            </div>

                            @if($q->question_type === 'multiple_choice' && is_array($q->options))
                                <ul class="qb-options">
                                    @foreach($q->options as $opt)<li>{{ $opt }}</li>@endforeach
                                </ul>
                            @endif
                        </div>
                    @empty
                        <div class="qb-empty">
                            <i class="ti ti-question-mark"></i>
                            <p class="mb-0">{{ __('No questions yet. Add the first one on the right.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Add Question form ─────────────────────────────────── --}}
        <div class="col-lg-5">
            <div class="card" style="position:sticky;top:80px;">
                <div class="card-header"><h5 class="mb-0"><i class="ti ti-plus me-2"></i>{{ __('Add Question') }}</h5></div>
                <div class="card-body">
                    @if($isLocked)
                        <p class="text-muted small mb-0"><i class="ti ti-lock me-1"></i>{{ __('Locked — survey already has responses.') }}</p>
                    @else
                    <form method="POST" action="{{ route('surveys.questions.store', $survey->id) }}" id="qForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('Question text') }} <span class="text-danger">*</span></label>
                            <textarea name="question_text" class="form-control" rows="2" maxlength="500" required
                                      placeholder="{{ __('e.g. How would you rate your work-life balance?') }}">{{ old('question_text') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">{{ __('Question type') }}</label>
                            <select name="question_type" id="qtype" class="form-control">
                                <option value="rating_5"        {{ old('question_type') === 'rating_5' ? 'selected' : '' }}>{{ __('Rating 1–5 (stars)') }}</option>
                                <option value="rating_10"       {{ old('question_type') === 'rating_10' ? 'selected' : '' }}>{{ __('Rating 0–10') }}</option>
                                <option value="yes_no"          {{ old('question_type') === 'yes_no' ? 'selected' : '' }}>{{ __('Yes / No') }}</option>
                                <option value="multiple_choice" {{ old('question_type') === 'multiple_choice' ? 'selected' : '' }}>{{ __('Multiple Choice') }}</option>
                                <option value="text"            {{ old('question_type') === 'text' ? 'selected' : '' }}>{{ __('Text Answer') }}</option>
                            </select>
                        </div>

                        {{-- Options builder (visible only for multiple_choice) --}}
                        <div class="mb-3 js-opts" style="display:none;">
                            <label class="form-label small fw-semibold">{{ __('Options') }}</label>
                            <div class="opts-list">
                                <div class="opt-row">
                                    <input type="text" name="options[]" class="form-control form-control-sm" placeholder="{{ __('Option 1') }}">
                                    <button type="button" class="btn btn-sm btn-light border js-rm-opt" tabindex="-1"><i class="ti ti-x"></i></button>
                                </div>
                                <div class="opt-row">
                                    <input type="text" name="options[]" class="form-control form-control-sm" placeholder="{{ __('Option 2') }}">
                                    <button type="button" class="btn btn-sm btn-light border js-rm-opt" tabindex="-1"><i class="ti ti-x"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-light border btn-sm mt-1 js-add-opt"><i class="ti ti-plus me-1"></i>{{ __('Add option') }}</button>
                        </div>

                        <div class="d-flex gap-3 mb-3 flex-wrap">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_required" value="0">
                                <input class="form-check-input" type="checkbox" id="req" name="is_required" value="1" checked>
                                <label class="form-check-label small" for="req">{{ __('Required') }}</label>
                            </div>
                            @if($survey->type === 'enps' && !$survey->questions->where('is_enps', true)->count())
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_enps" value="0">
                                <input class="form-check-input" type="checkbox" id="enps" name="is_enps" value="1">
                                <label class="form-check-label small" for="enps"><strong>{{ __('Mark as eNPS question') }}</strong></label>
                            </div>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><i class="ti ti-plus me-1"></i>{{ __('Add Question') }}</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
(function(){
    // Show options builder only for multiple_choice
    const qtype = document.getElementById('qtype');
    const opts  = document.querySelector('.js-opts');
    function syncOpts(){
        if (!qtype) return;
        if (opts) opts.style.display = qtype.value === 'multiple_choice' ? '' : 'none';
    }
    if (qtype) {
        qtype.addEventListener('change', syncOpts);
        syncOpts();
    }

    // eNPS auto-set type to rating_10 when checked
    const enps = document.getElementById('enps');
    if (enps && qtype) {
        enps.addEventListener('change', function(){
            if (enps.checked) {
                qtype.value = 'rating_10';
                syncOpts();
            }
        });
    }

    // Add / remove options
    const list = document.querySelector('.opts-list');
    const addBtn = document.querySelector('.js-add-opt');
    if (addBtn && list) {
        addBtn.addEventListener('click', function(){
            const div = document.createElement('div');
            div.className = 'opt-row';
            div.innerHTML = '<input type="text" name="options[]" class="form-control form-control-sm" placeholder="Option ' + (list.children.length + 1) + '"><button type="button" class="btn btn-sm btn-light border js-rm-opt" tabindex="-1"><i class="ti ti-x"></i></button>';
            list.appendChild(div);
        });
    }
    document.addEventListener('click', function(e){
        const rm = e.target.closest('.js-rm-opt');
        if (rm && list && list.querySelectorAll('.opt-row').length > 2) {
            rm.closest('.opt-row').remove();
        }
    });

    // Drag-and-drop reorder (HTML5 native — no extra library)
    @if(!$isLocked)
    const cards = document.querySelectorAll('.qb-card');
    let dragQid = null;
    cards.forEach(function(card){
        card.setAttribute('draggable', 'true');
        card.addEventListener('dragstart', function(e){
            dragQid = card.dataset.qid;
            card.style.opacity = '.4';
        });
        card.addEventListener('dragend', function(){
            card.style.opacity = '';
            saveOrder();
        });
        card.addEventListener('dragover', function(e){ e.preventDefault(); });
        card.addEventListener('drop', function(e){
            e.preventDefault();
            if (!dragQid || dragQid === card.dataset.qid) return;
            const dragged = document.querySelector('.qb-card[data-qid="' + dragQid + '"]');
            if (dragged) card.parentNode.insertBefore(dragged, card);
        });
    });

    function saveOrder(){
        const ids = Array.from(document.querySelectorAll('.qb-card')).map(c => c.dataset.qid);
        fetch('{{ route('surveys.questions.reorder', $survey->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({order: ids})
        }).then(r => r.json()).then(j => {
            if (j && j.ok) {
                // refresh number badges
                document.querySelectorAll('.qb-card .qb-num').forEach((el, i) => el.textContent = (i + 1));
            }
        }).catch(()=>{});
    }
    @endif
})();
</script>
@endpush
