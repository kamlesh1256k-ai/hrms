@extends('layouts.admin')
@section('page-title') {{ $survey->title }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('surveys.my') }}">{{ __('My Surveys') }}</a></li>
    <li class="breadcrumb-item">{{ $survey->title }}</li>
@endsection

@push('css-page')
<style>
    .fill-wrap{max-width:780px;margin:0 auto;}
    .fill-hero{background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);border-radius:14px;padding:24px 26px;color:#fff;margin-bottom:18px;box-shadow:0 8px 24px -10px rgba(99,102,241,.4);}
    .fill-hero h3{margin:0 0 6px 0;font-weight:700;}
    .fill-hero p{opacity:.9;margin:0;font-size:.92rem;}
    .fill-hero .anon-tag{display:inline-block;background:rgba(255,255,255,.2);padding:3px 12px;border-radius:20px;font-size:.7rem;font-weight:700;margin-top:8px;letter-spacing:.4px;text-transform:uppercase;}

    .progress-track{position:sticky;top:64px;z-index:5;background:#fff;padding:10px 0 8px;border-bottom:1px solid #e2e8f0;margin-bottom:18px;}
    .progress-bar-wrap{height:8px;background:#f1f5f9;border-radius:999px;overflow:hidden;}
    .progress-bar-fill{height:100%;background:linear-gradient(90deg,#6366f1,#8b5cf6);border-radius:999px;transition:width .25s ease;width:0%;}
    .progress-text{font-size:.78rem;color:#64748b;margin-top:6px;display:flex;justify-content:space-between;}

    .q-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;margin-bottom:14px;transition:border-color .15s,box-shadow .15s;}
    .q-card.is-answered{border-color:#10b981;background:linear-gradient(135deg,#f0fdf4 0%,#fff 30%);}
    .q-card.is-error{border-color:#ef4444;background:linear-gradient(135deg,#fef2f2 0%,#fff 30%);}
    .q-num{display:inline-flex;width:28px;height:28px;border-radius:50%;background:#eef2ff;color:#4338ca;font-weight:700;font-size:.78rem;align-items:center;justify-content:center;margin-right:8px;}
    .q-text{font-weight:600;color:#0f172a;font-size:.98rem;line-height:1.4;}
    .q-required{color:#ef4444;margin-left:4px;}

    /* Rating buttons */
    .rt-row{display:flex;flex-wrap:wrap;gap:6px;margin-top:12px;}
    .rt-btn{cursor:pointer;width:42px;height:42px;border-radius:10px;background:#f8fafc;border:2px solid #e2e8f0;color:#475569;font-weight:700;display:inline-flex;align-items:center;justify-content:center;transition:all .12s;}
    .rt-btn:hover{border-color:#94a3b8;background:#f1f5f9;}
    .rt-btn input{display:none;}
    .rt-btn.is-on{background:linear-gradient(135deg,#6366f1,#8b5cf6);border-color:#6366f1;color:#fff;box-shadow:0 4px 10px -4px rgba(99,102,241,.5);}
    .rt-scale{font-size:.7rem;color:#94a3b8;margin-top:4px;display:flex;justify-content:space-between;}

    /* Yes/No */
    .yn-row{display:flex;gap:8px;margin-top:12px;}
    .yn-btn{flex:0 0 auto;min-width:100px;padding:8px 18px;border-radius:8px;border:2px solid #e2e8f0;background:#fff;font-weight:600;cursor:pointer;transition:all .12s;}
    .yn-btn input{display:none;}
    .yn-btn.is-on.yes{background:#dcfce7;border-color:#16a34a;color:#15803d;}
    .yn-btn.is-on.no {background:#fee2e2;border-color:#dc2626;color:#991b1b;}

    /* MCQ */
    .mc-row{margin-top:12px;display:flex;flex-direction:column;gap:6px;}
    .mc-opt{padding:10px 12px;border:2px solid #e2e8f0;border-radius:9px;cursor:pointer;background:#fff;transition:all .12s;display:flex;align-items:center;gap:8px;}
    .mc-opt:hover{border-color:#94a3b8;background:#f8fafc;}
    .mc-opt input{margin:0;}
    .mc-opt.is-on{border-color:#6366f1;background:#eef2ff;color:#3730a3;font-weight:600;}

    .submit-wrap{position:sticky;bottom:0;background:#fff;border-top:1px solid #e2e8f0;padding:14px 0;display:flex;justify-content:space-between;align-items:center;gap:8px;}
</style>
@endpush

@section('content')
<div class="fill-wrap">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="fill-hero">
        <h3>{{ $survey->title }}</h3>
        @if($survey->description)
            <p>{{ $survey->description }}</p>
        @endif
        <div>
            <span class="anon-tag">
                @if($survey->is_anonymous)<i class="ti ti-eye-off me-1"></i>{{ __('Anonymous — your identity is hidden in reports') }}
                @else<i class="ti ti-user me-1"></i>{{ __('Identified response') }}
                @endif
            </span>
        </div>
    </div>

    <form method="POST" action="{{ route('surveys.my.submit', $survey->id) }}" id="fillForm" novalidate>
        @csrf

        {{-- Progress bar --}}
        <div class="progress-track">
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" id="pbFill"></div>
            </div>
            <div class="progress-text">
                <span><strong id="pbAnswered">0</strong> {{ __('of') }} {{ $survey->questions->count() }} {{ __('answered') }}</span>
                <span id="pbPercent">0%</span>
            </div>
        </div>

        @php $required = $survey->questions->where('is_required', true)->count(); @endphp

        @foreach($survey->questions as $i => $q)
            <div class="q-card" data-qid="{{ $q->id }}" data-required="{{ $q->is_required ? 1 : 0 }}" data-type="{{ $q->question_type }}">
                <div>
                    <span class="q-num">{{ $i + 1 }}</span>
                    <span class="q-text">{{ $q->question_text }}</span>
                    @if($q->is_required)<span class="q-required">*</span>@endif
                </div>

                @if($q->question_type === 'rating_5' || $q->question_type === 'rating_10')
                    @php $max = $q->question_type === 'rating_10' ? 10 : 5; $start = $q->question_type === 'rating_10' ? 0 : 1; @endphp
                    <div class="rt-row">
                        @for($n = $start; $n <= $max; $n++)
                            <label class="rt-btn">
                                <input type="radio" name="answers[{{ $q->id }}][rating]" value="{{ $n }}" required="{{ $q->is_required }}">
                                {{ $n }}
                            </label>
                        @endfor
                    </div>
                    <div class="rt-scale">
                        <span>{{ $q->question_type === 'rating_10' ? __('Not at all likely') : __('Strongly disagree') }}</span>
                        <span>{{ $q->question_type === 'rating_10' ? __('Extremely likely') : __('Strongly agree') }}</span>
                    </div>

                @elseif($q->question_type === 'yes_no')
                    <div class="yn-row">
                        <label class="yn-btn yes">
                            <input type="radio" name="answers[{{ $q->id }}][value]" value="yes">
                            <i class="ti ti-check me-1"></i>{{ __('Yes') }}
                        </label>
                        <label class="yn-btn no">
                            <input type="radio" name="answers[{{ $q->id }}][value]" value="no">
                            <i class="ti ti-x me-1"></i>{{ __('No') }}
                        </label>
                    </div>

                @elseif($q->question_type === 'multiple_choice')
                    <div class="mc-row">
                        @foreach((array)$q->options as $opt)
                            <label class="mc-opt">
                                <input type="radio" name="answers[{{ $q->id }}][value]" value="{{ $opt }}">
                                <span>{{ $opt }}</span>
                            </label>
                        @endforeach
                    </div>

                @elseif($q->question_type === 'text')
                    <textarea name="answers[{{ $q->id }}][text]" class="form-control mt-3" rows="3"
                              maxlength="2000" placeholder="{{ __('Type your answer here…') }}"></textarea>
                @endif
            </div>
        @endforeach

        <div class="submit-wrap">
            <small class="text-muted">
                <i class="ti ti-info-circle me-1"></i>
                {{ __('You can submit this survey only once.') }}
            </small>
            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                <i class="ti ti-send me-1"></i>{{ __('Submit') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('script-page')
<script>
(function(){
    const form = document.getElementById('fillForm');
    const cards = form.querySelectorAll('.q-card');
    const total = cards.length;
    const fill = document.getElementById('pbFill');
    const numEl = document.getElementById('pbAnswered');
    const pctEl = document.getElementById('pbPercent');

    function isCardAnswered(card){
        const type = card.dataset.type;
        if (type === 'text') {
            const ta = card.querySelector('textarea');
            return ta && ta.value.trim().length > 0;
        }
        const checked = card.querySelector('input[type=radio]:checked');
        return !!checked;
    }

    function refreshProgress(){
        let answered = 0;
        cards.forEach(card => {
            const ok = isCardAnswered(card);
            card.classList.toggle('is-answered', ok);
            card.classList.remove('is-error');
            if (ok) answered++;
        });
        const pct = total > 0 ? Math.round((answered / total) * 100) : 0;
        fill.style.width = pct + '%';
        numEl.textContent = answered;
        pctEl.textContent = pct + '%';
    }

    // Visual selection state for radio "buttons"
    form.addEventListener('change', function(e){
        const t = e.target;
        if (!t || t.tagName !== 'INPUT') return;
        if (t.type === 'radio') {
            // clear is-on for siblings, set on the chosen label
            const card = t.closest('.q-card');
            if (card) {
                card.querySelectorAll('.rt-btn, .yn-btn, .mc-opt').forEach(el => el.classList.remove('is-on'));
                t.closest('.rt-btn, .yn-btn, .mc-opt')?.classList.add('is-on');
            }
        }
        refreshProgress();
    });
    form.addEventListener('input', refreshProgress);

    // Submit-time validation: highlight unanswered required questions
    form.addEventListener('submit', function(e){
        let firstError = null;
        cards.forEach(card => {
            const required = card.dataset.required === '1';
            if (!required) return;
            if (!isCardAnswered(card)) {
                card.classList.add('is-error');
                if (!firstError) firstError = card;
            }
        });
        if (firstError) {
            e.preventDefault();
            firstError.scrollIntoView({behavior: 'smooth', block: 'center'});
            alert('{{ __("Please answer all required questions before submitting.") }}');
        }
    });

    refreshProgress();
})();
</script>
@endpush
