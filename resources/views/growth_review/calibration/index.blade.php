@extends('layouts.admin')
@section('page-title') {{ __('Calibration') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('growth-review.dashboard') }}">{{ __('Growth Review') }}</a></li>
    <li class="breadcrumb-item">{{ __('Calibration') }}</li>
@endsection
@push('css-page')
<style>
    .cal-frozen{background:#dcfce7;color:#166534;font-size:.7rem;padding:2px 8px;border-radius:15px;font-weight:600;}
    .cat-badge{font-size:.7rem;padding:3px 10px;border-radius:20px;font-weight:600;display:inline-block;min-width:90px;text-align:center;}
    .cat-Outstanding{background:#dcfce7;color:#166534;}
    .cat-Exceeds{background:#dbeafe;color:#1e40af;}
    .cat-Meets{background:#fef3c7;color:#92400e;}
    .cat-Low{background:#fee2e2;color:#991b1b;}
    .bell-summary{display:flex;gap:10px;flex-wrap:wrap;}
    .bell-box{flex:1;min-width:140px;padding:14px;border-radius:10px;text-align:center;border:1px solid var(--bs-border-color);}
    .bell-box .count{font-size:1.6rem;font-weight:700;line-height:1;}
    .bell-box .label{font-size:.72rem;color:var(--bs-secondary-color);text-transform:uppercase;letter-spacing:.5px;margin-top:4px;}
    .bell-box .pct{font-size:.7rem;color:var(--bs-secondary-color);}
    .orig-cal-arrow{color:#94a3b8;margin:0 6px;}

    /* ── Interactive Bell Curve Diagram ───────────────────────── */
    .bell-curve-wrap{position:relative;width:100%;background:linear-gradient(180deg,#f8fafc 0%,#fff 100%);border-radius:12px;padding:18px 18px 14px;border:1px solid var(--bs-border-color);}
    .bell-curve-svg{width:100%;height:170px;display:block;}
    .bell-zones{position:relative;display:grid;grid-template-columns:1fr 2fr 5fr 2fr;gap:6px;margin-top:-4px;min-height:140px;}
    .bell-zone{border-radius:10px;padding:10px 8px 8px;border:2px dashed transparent;transition:all .15s;position:relative;}
    .bell-zone.zone-Low{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.25);}
    .bell-zone.zone-Meets{background:rgba(245,158,11,.08);border-color:rgba(245,158,11,.25);}
    .bell-zone.zone-Exceeds{background:rgba(37,99,235,.08);border-color:rgba(37,99,235,.25);}
    .bell-zone.zone-Outstanding{background:rgba(22,163,74,.08);border-color:rgba(22,163,74,.25);}
    .bell-zone.drag-over{border-style:solid;transform:scale(1.02);box-shadow:0 4px 14px rgba(0,0,0,.08);}
    .bell-zone-head{display:flex;justify-content:space-between;align-items:center;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px;padding:0 4px;}
    .zone-Low .bell-zone-head{color:#991b1b;}
    .zone-Meets .bell-zone-head{color:#92400e;}
    .zone-Exceeds .bell-zone-head{color:#1e40af;}
    .zone-Outstanding .bell-zone-head{color:#166534;}
    .bell-zone-score{background:#fff;color:#0f172a;padding:1px 7px;border-radius:10px;font-size:.68rem;font-weight:700;border:1px solid rgba(0,0,0,.08);}
    .bell-chips{display:flex;flex-wrap:wrap;gap:4px;min-height:34px;}
    .bell-chip{display:inline-flex;align-items:center;gap:4px;background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:3px 9px;font-size:.72rem;font-weight:600;color:#0f172a;cursor:grab;user-select:none;box-shadow:0 1px 2px rgba(0,0,0,.04);transition:transform .1s,box-shadow .1s;}
    .bell-chip:hover{transform:translateY(-1px);box-shadow:0 3px 8px rgba(0,0,0,.1);}
    .bell-chip:active{cursor:grabbing;}
    .bell-chip.is-frozen{cursor:not-allowed;opacity:.65;background:#f1f5f9;}
    .bell-chip.dragging{opacity:.4;}
    .bell-chip .chip-orig{font-size:.62rem;color:#64748b;font-weight:500;}
    .bell-chip .chip-lock{color:#16a34a;font-size:.7rem;}
    .bell-curve-hint{font-size:.72rem;color:var(--bs-secondary-color);margin-top:8px;display:flex;align-items:center;gap:6px;}
</style>
@endpush
@section('content')
    @include('growth_review._nav')
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    {{-- ── Filters: Cycle + Department ───────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <form class="d-flex align-items-end gap-3 flex-wrap" method="GET">
                <div>
                    <label class="form-label mb-1">{{ __('Cycle') }}</label>
                    <select name="cycle_id" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:200px;">
                        @foreach($cycles as $c)
                            <option value="{{ $c->id }}" {{ $cycleId==$c->id?'selected':'' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label mb-1">{{ __('Department') }}</label>
                    <select name="department_id" class="form-control form-control-sm" onchange="this.form.submit()" style="min-width:200px;">
                        <option value="">{{ __('— All departments —') }}</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ $deptId==$d->id?'selected':'' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($cycle)
                    <span class="text-muted" style="font-size:.82rem;">
                        {{ __('Status') }}: <strong>{{ ucfirst($cycle->status) }}</strong>
                    </span>
                @endif
            </form>
        </div>
    </div>

    {{-- ── Bell Curve Distribution Summary ───────────────────────── --}}
    @if($ratings->isNotEmpty())
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h6 class="mb-0"><i class="ti ti-chart-bar me-1"></i>{{ __('Bell Curve Distribution') }}
                    @if($deptId)
                        <small class="text-muted ms-2">— {{ $departments->firstWhere('id', (int)$deptId)->name ?? '' }}</small>
                    @else
                        <small class="text-muted ms-2">— {{ __('All departments') }}</small>
                    @endif
                </h6>
                <form method="POST" action="{{ route('growth-review.calibration.bell-curve') }}"
                      onsubmit="return confirm('Apply Bell Curve to {{ $ratings->count() }} employees? This overwrites existing calibration for non-frozen rows.')">
                    @csrf
                    <input type="hidden" name="cycle_id" value="{{ $cycleId }}">
                    <input type="hidden" name="department_id" value="{{ $deptId }}">
                    <button class="btn btn-primary btn-sm">
                        <i class="ti ti-wand me-1"></i>{{ __('Apply Bell Curve') }}
                    </button>
                </form>
            </div>
            <div class="bell-summary">
                <div class="bell-box" style="border-left:4px solid #16a34a;">
                    <div class="count" style="color:#166534;">{{ $distribution['Outstanding'] }}</div>
                    <div class="label">{{ __('Outstanding') }}</div>
                    <div class="pct">{{ __('Top 10%') }}</div>
                </div>
                <div class="bell-box" style="border-left:4px solid #2563eb;">
                    <div class="count" style="color:#1e40af;">{{ $distribution['Exceeds'] }}</div>
                    <div class="label">{{ __('Exceeds') }}</div>
                    <div class="pct">{{ __('Next 20%') }}</div>
                </div>
                <div class="bell-box" style="border-left:4px solid #f59e0b;">
                    <div class="count" style="color:#92400e;">{{ $distribution['Meets'] }}</div>
                    <div class="label">{{ __('Meets') }}</div>
                    <div class="pct">{{ __('Next 50%') }}</div>
                </div>
                <div class="bell-box" style="border-left:4px solid #ef4444;">
                    <div class="count" style="color:#991b1b;">{{ $distribution['Low'] }}</div>
                    <div class="label">{{ __('Low Performance') }}</div>
                    <div class="pct">{{ __('Bottom 20%') }}</div>
                </div>
                <div class="bell-box" style="border-left:4px solid #64748b;">
                    <div class="count">{{ $ratings->count() }}</div>
                    <div class="label">{{ __('Total') }}</div>
                    <div class="pct">{{ __('In scope') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Interactive Bell Curve Diagram (drag chips between zones) ── --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <h6 class="mb-0"><i class="ti ti-chart-histogram me-1"></i>{{ __('Interactive Bell Curve') }}
                    <small class="text-muted ms-2">{{ __('Drag employees between zones to recalibrate') }}</small>
                </h6>
                <small class="text-muted"><i class="ti ti-info-circle me-1"></i>{{ __('Frozen rows cannot be moved. Click "Save Calibration" below to persist.') }}</small>
            </div>

            <div class="bell-curve-wrap">
                {{-- SVG bell curve with 4 colored zones (10/20/50/20) --}}
                <svg class="bell-curve-svg" viewBox="0 0 1000 170" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="bcLow" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#ef4444" stop-opacity=".55"/>
                            <stop offset="100%" stop-color="#ef4444" stop-opacity=".05"/>
                        </linearGradient>
                        <linearGradient id="bcMeets" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#f59e0b" stop-opacity=".55"/>
                            <stop offset="100%" stop-color="#f59e0b" stop-opacity=".05"/>
                        </linearGradient>
                        <linearGradient id="bcExceeds" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#2563eb" stop-opacity=".55"/>
                            <stop offset="100%" stop-color="#2563eb" stop-opacity=".05"/>
                        </linearGradient>
                        <linearGradient id="bcOut" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#16a34a" stop-opacity=".6"/>
                            <stop offset="100%" stop-color="#16a34a" stop-opacity=".05"/>
                        </linearGradient>
                    </defs>
                    {{-- Bell curve shape: Low (0-100), Meets (100-300), Exceeds (300-800), Outstanding (800-1000)
                         Wait — distribution is Low 20% / Meets 50% / Exceeds 20% / Outstanding 10% from low→high.
                         Mapping x: 0..200 = Low, 200..700 = Meets, 700..900 = Exceeds, 900..1000 = Outstanding --}}
                    <path d="M0,160 C 100,160 150,150 200,130 L 200,160 Z" fill="url(#bcLow)"/>
                    <path d="M200,130 C 300,90 400,30 450,15 C 500,5 550,5 600,15 C 650,30 700,90 700,90 L 700,160 L 200,160 Z" fill="url(#bcMeets)"/>
                    <path d="M700,90 C 750,110 800,130 900,150 L 900,160 L 700,160 Z" fill="url(#bcExceeds)"/>
                    <path d="M900,150 C 940,156 970,159 1000,160 L 1000,160 L 900,160 Z" fill="url(#bcOut)"/>
                    {{-- Outline curve --}}
                    <path d="M0,160 C 100,160 150,150 200,130 C 300,90 400,30 450,15 C 500,5 550,5 600,15 C 650,30 700,90 800,130 C 900,150 950,158 1000,160"
                          fill="none" stroke="#475569" stroke-width="1.5" opacity=".6"/>
                    {{-- Divider lines --}}
                    <line x1="200" y1="0" x2="200" y2="160" stroke="#94a3b8" stroke-width="1" stroke-dasharray="3,3"/>
                    <line x1="700" y1="0" x2="700" y2="160" stroke="#94a3b8" stroke-width="1" stroke-dasharray="3,3"/>
                    <line x1="900" y1="0" x2="900" y2="160" stroke="#94a3b8" stroke-width="1" stroke-dasharray="3,3"/>
                    {{-- Labels --}}
                    <text x="100" y="155" text-anchor="middle" font-size="11" fill="#991b1b" font-weight="600">Low 20%</text>
                    <text x="450" y="155" text-anchor="middle" font-size="11" fill="#92400e" font-weight="600">Meets 50%</text>
                    <text x="800" y="155" text-anchor="middle" font-size="11" fill="#1e40af" font-weight="600">Exceeds 20%</text>
                    <text x="950" y="155" text-anchor="middle" font-size="11" fill="#166534" font-weight="600">Top 10%</text>
                </svg>

                {{-- Drop zones aligned roughly under each curve segment --}}
                <div class="bell-zones">
                    @foreach(['Low'=>'C', 'Meets'=>'B', 'Exceeds'=>'A', 'Outstanding'=>'A+'] as $zone => $grade)
                        <div class="bell-zone zone-{{ $zone }}" data-zone="{{ $zone }}" data-grade="{{ $grade }}">
                            <div class="bell-zone-head">
                                <span>{{ __($zone) }}</span>
                                <span class="bell-zone-score">{{ __('Grade') }} {{ $grade }}</span>
                            </div>
                            <div class="bell-chips" data-chips-for="{{ $zone }}">
                                @foreach($ratings as $r)
                                    @if($r->calibration_category === $zone)
                                        <span class="bell-chip {{ $r->is_frozen ? 'is-frozen' : '' }}"
                                              draggable="{{ $r->is_frozen ? 'false' : 'true' }}"
                                              data-rating-id="{{ $r->id }}"
                                              data-orig="{{ $r->manager_rating ?? '—' }}"
                                              title="{{ $r->employee->name ?? '' }} · {{ __('Manager') }}: {{ $r->manager_rating ?? '—' }}">
                                            @if($r->is_frozen)<i class="ti ti-lock chip-lock"></i>@endif
                                            {{ \Illuminate\Support\Str::limit($r->employee->name ?? '—', 18) }}
                                            <span class="chip-orig">({{ $r->manager_rating ?? '—' }})</span>
                                        </span>
                                    @endif
                                @endforeach
                                {{-- Uncategorized: show in Meets zone by default for visibility --}}
                                @if($zone === 'Meets')
                                    @foreach($ratings as $r)
                                        @if(!$r->calibration_category)
                                            <span class="bell-chip {{ $r->is_frozen ? 'is-frozen' : '' }}"
                                                  draggable="{{ $r->is_frozen ? 'false' : 'true' }}"
                                                  data-rating-id="{{ $r->id }}"
                                                  data-orig="{{ $r->manager_rating ?? '—' }}"
                                                  data-uncategorized="1"
                                                  title="{{ $r->employee->name ?? '' }} · {{ __('Uncategorized') }} · {{ __('Manager') }}: {{ $r->manager_rating ?? '—' }}">
                                                @if($r->is_frozen)<i class="ti ti-lock chip-lock"></i>@endif
                                                {{ \Illuminate\Support\Str::limit($r->employee->name ?? '—', 18) }}
                                                <span class="chip-orig">({{ $r->manager_rating ?? '—' }})</span>
                                            </span>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="bell-curve-hint">
                    <i class="ti ti-hand-grab"></i>
                    {{ __('Tip: drag chips between zones. The "Calibrated" score and Grade in the table below update automatically. Don\'t forget to click Save Calibration.') }}
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Ratings table: Original vs Calibrated ─────────────────── --}}
    @if($ratings->isEmpty())
        <div class="alert alert-info">
            <i class="ti ti-info-circle me-1"></i>
            {{ __('No ratings to calibrate. Reviews need to be submitted first, and managers must enter original ratings.') }}
        </div>
    @else
    <form method="POST" action="{{ route('growth-review.calibration.update') }}">@csrf
        <div class="card">
            <div class="card-body table-border-style">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Employee') }}</th>
                                <th>{{ __('Department') }}</th>
                                <th class="text-center">{{ __('Self') }}</th>
                                <th class="text-center" style="background:#fef9c3;">{{ __('Original (Manager)') }}</th>
                                <th class="text-center">{{ __('Head') }}</th>
                                <th class="text-center" style="background:#dbeafe;">{{ __('Calibrated') }}</th>
                                <th>{{ __('Grade') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Notes') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ratings as $r)
                            @php
                                $orig = $r->manager_rating;
                                $cal  = $r->final_rating;
                                $changed = $orig !== null && $cal !== null && (float)$orig !== (float)$cal;
                                $deltaUp = $changed && (float)$cal > (float)$orig;
                                $deltaStyle = 'font-size:.65rem;margin-top:2px;color:' . ($deltaUp ? '#059669' : '#dc2626') . ';';
                                if (!$changed) { $deltaStyle .= 'display:none;'; }
                            @endphp
                            <tr data-row-rating-id="{{ $r->id }}">
                                <td>
                                    <strong>{{ $r->employee->name ?? '—' }}</strong>
                                    <input type="hidden" name="ratings[{{ $loop->index }}][id]" value="{{ $r->id }}">
                                </td>
                                <td><small class="text-muted">{{ $r->employee->department->name ?? '—' }}</small></td>
                                <td class="text-center">{{ $r->self_rating !== null ? number_format((float)$r->self_rating, 1) : '—' }}</td>
                                <td class="text-center" style="background:#fef9c3;">
                                    <strong>{{ $orig !== null ? number_format((float)$orig, 1) : '—' }}</strong>
                                </td>
                                <td class="text-center">{{ $r->head_rating !== null ? number_format((float)$r->head_rating, 1) : '—' }}</td>
                                <td style="background:#dbeafe;">
                                    <input type="number" name="ratings[{{ $loop->index }}][final_rating]"
                                           class="form-control form-control-sm text-center js-final-rating"
                                           data-orig="{{ $orig ?? '' }}"
                                           value="{{ $cal ?? '' }}"
                                           min="0" max="5" step="0.1" style="width:80px;" required
                                           {{ $r->is_frozen ? 'disabled' : '' }}>
                                    <div class="js-delta" style="{{ $deltaStyle }}">
                                        @if($changed)
                                            {{ $deltaUp ? '▲' : '▼' }} {{ number_format(abs((float)$cal - (float)$orig), 1) }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <select name="ratings[{{ $loop->index }}][grade]" class="form-control form-control-sm js-grade" style="width:80px;" {{ $r->is_frozen ? 'disabled' : '' }}>
                                        <option value="">—</option>
                                        @foreach(['A+','A','B+','B','C+','C','D'] as $g)
                                            <option value="{{ $g }}" {{ $r->grade==$g?'selected':'' }}>{{ $g }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="js-category-cell">
                                    @if($r->calibration_category)
                                        <span class="cat-badge cat-{{ $r->calibration_category }}">{{ $r->calibration_category }}</span>
                                    @else
                                        <span class="text-muted" style="font-size:.75rem;">—</span>
                                    @endif
                                </td>
                                <td>
                                    <input type="text" name="ratings[{{ $loop->index }}][calibration_notes]"
                                           class="form-control form-control-sm" value="{{ $r->calibration_notes }}"
                                           placeholder="Notes..." {{ $r->is_frozen ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    @if($r->is_frozen)
                                        <span class="cal-frozen"><i class="ti ti-lock me-1"></i>{{ __('Frozen') }}</span>
                                    @elseif($r->is_calibrated)
                                        <span class="badge bg-info">{{ __('Calibrated') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Pending') }}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-adjustments me-1"></i>{{ __('Save Calibration') }}
            </button>
        </div>
    </form>

    {{-- ── Freeze button (separate form — submits only cycle_id) ─── --}}
    <form method="POST" action="{{ route('growth-review.calibration.freeze') }}" class="mt-2"
          onsubmit="return confirm('Freeze all calibrated ratings? This finalizes the cycle and prevents any further edits.')">
        @csrf
        <input type="hidden" name="cycle_id" value="{{ $cycleId }}">
        <button type="submit" class="btn btn-danger">
            <i class="ti ti-lock me-1"></i>{{ __('Final Freeze — Lock All Ratings') }}
        </button>
    </form>
    @endif
@endsection

@push('script-page')
<script>
(function(){
    // Drag only changes category + grade. final_rating stays as whatever
    // the manager gave (or whatever HR has typed) — HR adjusts it manually.
    const ZONE_META = {
        'Outstanding': { grade: 'A+' },
        'Exceeds':     { grade: 'A'  },
        'Meets':       { grade: 'B'  },
        'Low':         { grade: 'C'  },
    };

    const zones = document.querySelectorAll('.bell-zone');
    if (!zones.length) return;

    let dragId = null;

    document.querySelectorAll('.bell-chip').forEach(chip => {
        chip.addEventListener('dragstart', e => {
            if (chip.classList.contains('is-frozen')) { e.preventDefault(); return; }
            dragId = chip.dataset.ratingId;
            chip.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', dragId);
        });
        chip.addEventListener('dragend', () => {
            chip.classList.remove('dragging');
            dragId = null;
            zones.forEach(z => z.classList.remove('drag-over'));
        });
    });

    zones.forEach(zone => {
        zone.addEventListener('dragover', e => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            zone.classList.add('drag-over');
        });
        zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            const id = e.dataTransfer.getData('text/plain') || dragId;
            if (!id) return;
            const chip = document.querySelector('.bell-chip[data-rating-id="'+id+'"]');
            if (!chip || chip.classList.contains('is-frozen')) return;

            const newZone = zone.dataset.zone;
            const target  = zone.querySelector('.bell-chips');
            if (chip.parentElement === target) return; // same bucket — no change

            target.appendChild(chip);
            chip.removeAttribute('data-uncategorized');
            applyZoneToRow(id, newZone);
            updateDistributionCounts();
        });
    });

    function applyZoneToRow(ratingId, zone){
        const meta = ZONE_META[zone];
        const row  = document.querySelector('tr[data-row-rating-id="'+ratingId+'"]');
        if (!row || !meta) return;

        // Grade select (auto-update based on bucket)
        const gradeEl = row.querySelector('.js-grade');
        if (gradeEl && !gradeEl.disabled) {
            const opt = Array.from(gradeEl.options).find(o => o.value === meta.grade);
            if (opt) gradeEl.value = meta.grade;
        }

        // Category badge cell
        const catCell = row.querySelector('.js-category-cell');
        if (catCell) {
            catCell.innerHTML = '<span class="cat-badge cat-'+zone+'">'+zone+'</span>';
        }
        // Note: final_rating input is intentionally NOT touched. HR types
        // their own number if they want to override the manager's rating.
    }

    function updateDistributionCounts(){
        // Top summary banner counts
        const counts = { Outstanding:0, Exceeds:0, Meets:0, Low:0 };
        Object.keys(counts).forEach(z => {
            const wrap = document.querySelector('.bell-chips[data-chips-for="'+z+'"]');
            if (wrap) counts[z] = wrap.querySelectorAll('.bell-chip').length;
        });
        const boxes = document.querySelectorAll('.bell-summary .bell-box .count');
        // boxes order: Outstanding, Exceeds, Meets, Low, Total
        if (boxes.length >= 4) {
            boxes[0].textContent = counts.Outstanding;
            boxes[1].textContent = counts.Exceeds;
            boxes[2].textContent = counts.Meets;
            boxes[3].textContent = counts.Low;
        }
    }
})();
</script>
@endpush
