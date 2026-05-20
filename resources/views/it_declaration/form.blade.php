@extends('layouts.admin')
@section('page-title')
    {{ __('Employee IT Declaration') }}
@endsection

@section('content')
@php
    $actionUrl = $declaration ? route('it.declaration.update', $declaration->id) : route('it.declaration.store');
    $invRows = old('investments', $investments->map(fn($r)=>['section_code'=>$r->section_code,'type'=>$r->investment_type,'amount'=>$r->amount])->toArray());
    $exRows = old('exemptions', $exemptions->map(fn($r)=>['section_code'=>$r->section_code,'type'=>$r->exemption_type,'amount'=>$r->amount])->toArray());
    $incRows = old('incomes', $incomes->map(fn($r)=>['type'=>$r->income_type,'amount'=>$r->amount])->toArray());
    if (empty($invRows)) $invRows = [['section_code'=>'80C','type'=>'LIC','amount'=>'']];
    if (empty($exRows)) $exRows = [['section_code'=>'80D','type'=>'Self/Family Insurance','amount'=>'']];
    if (empty($incRows)) $incRows = [['type'=>'Interest','amount'=>'']];
@endphp

<form method="POST" action="{{ $actionUrl }}">
    @csrf

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">{{ __('Employee Details') }}</h5></div>
        <div class="card-body">
            <div class="row">
                @php $isAdmin = in_array(\Auth::user()->type, ['company', 'super admin']); @endphp
                @if($isAdmin && isset($employees) && is_object($employees) && $employees->count() > 0)
                    <div class="col-md-3 mb-2">
                        <label class="form-label">{{ __('Select Employee') }} <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">{{ __('-- Select --') }}</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ (int)old('employee_id', ($employee ? $employee->id : '')) === (int)$emp->id ? 'selected' : '' }}>{{ $emp->name }} ({{ $emp->employee_id }})</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="col-md-3 mb-2"><label class="form-label">{{ __('Employee Name') }}</label><input class="form-control" value="{{ $employee->name ?? '' }}" readonly></div>
                @endif
                <div class="col-md-3 mb-2"><label class="form-label">{{ __('Financial Year') }}</label><input name="financial_year" class="form-control" value="{{ old('financial_year', $declaration->financial_year ?? $fy) }}" required></div>
                <div class="col-md-3 mb-2">
                    <label class="form-label">{{ __('Tax Regime') }}</label>
                    <select name="tax_regime" class="form-control" required>
                        <option value="new" {{ old('tax_regime', $declaration->tax_regime ?? 'new') === 'new' ? 'selected' : '' }}>{{ __('New Regime (Default FY 25-26)') }}</option>
                        <option value="old" {{ old('tax_regime', $declaration->tax_regime ?? 'new') === 'old' ? 'selected' : '' }}>{{ __('Old Regime') }}</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2"><label class="form-label">{{ __('Status') }}</label><input class="form-control" value="{{ ucfirst($declaration->declaration_status ?? 'draft') }}" readonly></div>
            </div>
        </div>
    </div>

    <div class="alert alert-info d-none" id="newRegimeWarning" style="border-left:4px solid #3b82f6;">
        <i class="ti ti-info-circle me-1"></i>
        <strong>{{ __('New Regime Selected:') }}</strong>
        {{ __('Under New Tax Regime, investments (80C, 80D), HRA exemption, and Home Loan deductions are NOT applicable. Only ₹75,000 standard deduction is allowed. These sections below are only used if you switch to Old Regime.') }}
    </div>
    <div class="alert alert-success d-none" id="oldRegimeInfo" style="border-left:4px solid #22c55e;">
        <i class="ti ti-check-circle me-1"></i>
        <strong>{{ __('Old Regime Selected:') }}</strong>
        {{ __('Your investments (80C, 80D), HRA, and Home Loan deductions will reduce your taxable income. Fill all applicable sections below.') }}
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">{{ __('House & Income Details') }}</h5></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2 form-check"><input class="form-check-input" type="checkbox" name="is_rented_house" id="is_rented_house" {{ old('is_rented_house', $declaration->is_rented_house ?? false) ? 'checked' : '' }}><label class="form-check-label" for="is_rented_house">{{ __('Staying in rented house?') }}</label></div>
                <div class="col-md-4 mb-2 form-check"><input class="form-check-input" type="checkbox" name="is_home_loan" id="is_home_loan" {{ old('is_home_loan', $declaration->is_home_loan ?? false) ? 'checked' : '' }}><label class="form-check-label" for="is_home_loan">{{ __('Repaying home loan?') }}</label></div>
                <div class="col-md-4 mb-2 form-check"><input class="form-check-input" type="checkbox" name="is_rental_income" id="is_rental_income" {{ old('is_rental_income', $declaration->is_rental_income ?? false) ? 'checked' : '' }}><label class="form-check-label" for="is_rental_income">{{ __('Receiving rental income?') }}</label></div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-2"><label class="form-label">{{ __('Rent Paid') }}</label><input type="number" min="0" step="0.01" name="rent_paid" class="form-control" value="{{ old('rent_paid', $declaration->rent_paid ?? 0) }}"></div>
                <div class="col-md-3 mb-2"><label class="form-label">{{ __('Landlord Name') }}</label><input name="landlord_name" class="form-control" value="{{ old('landlord_name', $declaration->landlord_name ?? '') }}"></div>
                <div class="col-md-3 mb-2"><label class="form-label">{{ __('Landlord PAN') }}</label><input name="landlord_pan" class="form-control" value="{{ old('landlord_pan', $declaration->landlord_pan ?? '') }}"></div>
                <div class="col-md-3 mb-2"><label class="form-label">{{ __('Interest on Home Loan') }}</label><input type="number" min="0" step="0.01" name="home_loan_interest" class="form-control" value="{{ old('home_loan_interest', $declaration->home_loan_interest ?? 0) }}"></div>
                <div class="col-md-3 mb-2"><label class="form-label">{{ __('Rental Income Amount') }}</label><input type="number" min="0" step="0.01" name="rental_income_amount" class="form-control" value="{{ old('rental_income_amount', $declaration->rental_income_amount ?? 0) }}"></div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between"><h5 class="mb-0">{{ __('Other Sources of Income') }}</h5><button type="button" id="add-income" class="btn btn-sm btn-outline-primary">+</button></div>
        <div class="card-body" id="income-wrap">
            @foreach($incRows as $i => $r)
                <div class="row mb-2 income-row">
                    <div class="col-md-6"><input class="form-control" name="incomes[{{ $i }}][type]" placeholder="{{ __('Type') }}" value="{{ $r['type'] ?? '' }}"></div>
                    <div class="col-md-5"><input type="number" min="0" step="0.01" class="form-control" name="incomes[{{ $i }}][amount]" placeholder="{{ __('Amount') }}" value="{{ $r['amount'] ?? '' }}"></div>
                    <div class="col-md-1"><button type="button" class="btn btn-sm btn-light remove-row">x</button></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between"><h5 class="mb-0">{{ __('80C Investments (Max 150000)') }}</h5><button type="button" id="add-investment" class="btn btn-sm btn-outline-primary">+</button></div>
        <div class="card-body" id="investment-wrap">
            @foreach($invRows as $i => $r)
                <div class="row mb-2 investment-row">
                    <div class="col-md-4">
                        <select class="form-control" name="investments[{{ $i }}][type]">
                            @foreach(['LIC','PPF','ELSS','EPF','Tax Saver FD','NPS (Voluntary)','Education Loan Interest','Medical Expenditure'] as $opt)
                                <option value="{{ $opt }}" {{ ($r['type'] ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><input type="text" class="form-control" name="investments[{{ $i }}][section_code]" value="{{ $r['section_code'] ?? '80C' }}"></div>
                    <div class="col-md-4"><input type="number" min="0" step="0.01" class="form-control investment-amount" name="investments[{{ $i }}][amount]" value="{{ $r['amount'] ?? '' }}"></div>
                    <div class="col-md-1"><button type="button" class="btn btn-sm btn-light remove-row">x</button></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between"><h5 class="mb-0">{{ __('80D / Other Exemptions (Max 100000 for 80D)') }}</h5><button type="button" id="add-exemption" class="btn btn-sm btn-outline-primary">+</button></div>
        <div class="card-body" id="exemption-wrap">
            @foreach($exRows as $i => $r)
                <div class="row mb-2 exemption-row">
                    <div class="col-md-4">
                        <select class="form-control" name="exemptions[{{ $i }}][type]">
                            @foreach(['Self/Family Insurance','Parents Insurance','Other Allowance','HRA Exemption'] as $opt)
                                <option value="{{ $opt }}" {{ ($r['type'] ?? '') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><input type="text" class="form-control" name="exemptions[{{ $i }}][section_code]" value="{{ $r['section_code'] ?? '80D' }}"></div>
                    <div class="col-md-4"><input type="number" min="0" step="0.01" class="form-control exemption-amount" name="exemptions[{{ $i }}][amount]" value="{{ $r['amount'] ?? '' }}"></div>
                    <div class="col-md-1"><button type="button" class="btn btn-sm btn-light remove-row">x</button></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="text-end d-flex justify-content-end gap-2">
        <a href="{{ route('it.declaration.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
        <button type="submit" name="action_type" value="draft" class="btn btn-outline-primary">{{ __('Save as Draft') }}</button>
        <button type="submit" name="action_type" value="submit" class="btn btn-primary">{{ __('Submit Declaration') }}</button>
        <button type="submit" name="action_type" value="submit" class="btn btn-success" onclick="this.form.compare.value=1;">{{ __('Submit & Compare') }}</button>
        <input type="hidden" name="compare" value="0">
    </div>
</form>

@if(!empty($declaration) && !empty($declaration->compare_json))
    @php
        $cmp = $declaration->compare_json;
        $oldTax = (float)($cmp['old_regime']['estimated_tax'] ?? 0);
        $newTax = (float)($cmp['new_regime']['estimated_tax'] ?? 0);
        $oldMonthly = $oldTax > 0 ? round($oldTax / 12) : 0;
        $newMonthly = $newTax > 0 ? round($newTax / 12) : 0;
        $saving = round(abs($oldTax - $newTax));
        $savingMonthly = round($saving / 12);
        $recommended = strtoupper($cmp['recommended'] ?? '-');
    @endphp
    <div class="card mt-3">
        <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="ti ti-scale me-2"></i>{{ __('Old vs New Regime — Tax Comparison') }}</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 rounded" style="background:#fef2f2;border:1px solid #fecaca;">
                        <h6 class="fw-bold text-danger mb-2">{{ __('Old Regime') }}</h6>
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ __('Annual Tax') }}</span>
                            <strong style="font-size:1.1rem;">{{ \Auth::user()->priceFormat((int)round($oldTax)) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>{{ __('Monthly TDS') }}</span>
                            <strong>{{ \Auth::user()->priceFormat((int)$oldMonthly) }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        <h6 class="fw-bold text-success mb-2">{{ __('New Regime (FY 25-26)') }}</h6>
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ __('Annual Tax') }}</span>
                            <strong style="font-size:1.1rem;">{{ \Auth::user()->priceFormat((int)round($newTax)) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>{{ __('Monthly TDS') }}</span>
                            <strong>{{ \Auth::user()->priceFormat((int)$newMonthly) }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="p-3 rounded text-center" style="background:#eff6ff;border:1px solid #bfdbfe;">
                        <span class="badge bg-{{ $recommended === 'NEW' ? 'success' : 'warning' }} fs-6 px-3 py-2">
                            <i class="ti ti-trophy me-1"></i>{{ __('Recommended:') }} {{ $recommended }} {{ __('REGIME') }}
                        </span>
                        <div class="mt-2" style="font-size:.85rem;color:#475569;">
                            {{ __('You save') }} <strong>{{ \Auth::user()->priceFormat((int)$saving) }}</strong> {{ __('per year') }}
                            ({{ \Auth::user()->priceFormat((int)$savingMonthly) }} {{ __('/month') }})
                            {{ __('by choosing') }} {{ $recommended }} {{ __('regime') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@push('script-page')
<script>
    (function(){
        function addRow(btnId, wrapId, getTemplate) {
            var btn = document.getElementById(btnId);
            var wrap = document.getElementById(wrapId);
            if (!btn || !wrap) return;
            btn.addEventListener('click', function () {
                var idx = wrap.querySelectorAll('.row').length;
                wrap.insertAdjacentHTML('beforeend', getTemplate(idx));
            });
            wrap.addEventListener('click', function(e){
                if (e.target.classList.contains('remove-row')) {
                    e.target.closest('.row').remove();
                }
            });
        }
        addRow('add-income','income-wrap',function(i){return '<div class="row mb-2 income-row"><div class="col-md-6"><input class="form-control" name="incomes['+i+'][type]" placeholder="Type"></div><div class="col-md-5"><input type="number" min="0" step="0.01" class="form-control" name="incomes['+i+'][amount]" placeholder="Amount"></div><div class="col-md-1"><button type="button" class="btn btn-sm btn-light remove-row">x</button></div></div>';});
        addRow('add-investment','investment-wrap',function(i){return '<div class="row mb-2 investment-row"><div class="col-md-4"><input class="form-control" name="investments['+i+'][type]" placeholder="Investment type"></div><div class="col-md-3"><input class="form-control" name="investments['+i+'][section_code]" value="80C"></div><div class="col-md-4"><input type="number" min="0" step="0.01" class="form-control investment-amount" name="investments['+i+'][amount]" placeholder="Amount"></div><div class="col-md-1"><button type="button" class="btn btn-sm btn-light remove-row">x</button></div></div>';});
        addRow('add-exemption','exemption-wrap',function(i){return '<div class="row mb-2 exemption-row"><div class="col-md-4"><input class="form-control" name="exemptions['+i+'][type]" placeholder="Exemption type"></div><div class="col-md-3"><input class="form-control" name="exemptions['+i+'][section_code]" value="80D"></div><div class="col-md-4"><input type="number" min="0" step="0.01" class="form-control exemption-amount" name="exemptions['+i+'][amount]" placeholder="Amount"></div><div class="col-md-1"><button type="button" class="btn btn-sm btn-light remove-row">x</button></div></div>';});

        // Show/hide regime warning
        function toggleRegimeWarning() {
            var sel = document.querySelector('select[name="tax_regime"]');
            if (!sel) return;
            var nw = document.getElementById('newRegimeWarning');
            var ow = document.getElementById('oldRegimeInfo');
            if (sel.value === 'new') {
                if (nw) nw.classList.remove('d-none');
                if (ow) ow.classList.add('d-none');
            } else {
                if (nw) nw.classList.add('d-none');
                if (ow) ow.classList.remove('d-none');
            }
        }
        var regimeSel = document.querySelector('select[name="tax_regime"]');
        if (regimeSel) {
            regimeSel.addEventListener('change', toggleRegimeWarning);
            toggleRegimeWarning();
        }
    })();
</script>
@endpush

