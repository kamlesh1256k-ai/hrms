@extends('layouts.admin')

@section('page-title') {{ __('Add to Talent Pool') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.talent-pool.index') }}">{{ __('Talent Pool') }}</a></li>
    <li class="breadcrumb-item">{{ __('Add Candidate') }}</li>
@endsection

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-user-plus me-1 text-primary"></i>{{ __('Add Candidate to Talent Pool') }}</h6>
            <p class="text-muted small mb-0 mt-1">
                {{ __('Add a sourced candidate (LinkedIn, referral, outbound) for future roles. They will not appear on any current job pipeline.') }}
            </p>
        </div>
        <form method="POST" action="{{ route('recruitment.talent-pool.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    {{-- Basics --}}
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Full Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control" required maxlength="200">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Email') }} <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required maxlength="200">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Phone') }}</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" maxlength="50">
                    </div>

                    {{-- Background --}}
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Current Company') }}</label>
                        <input type="text" name="current_company" value="{{ old('current_company') }}" class="form-control" maxlength="200">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Current Designation') }}</label>
                        <input type="text" name="current_designation" value="{{ old('current_designation') }}" class="form-control" maxlength="200">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Experience (years)') }}</label>
                        <input type="number" step="0.5" name="experience_years" value="{{ old('experience_years') }}" class="form-control" min="0" max="60">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">{{ __('Skills') }}</label>
                        <input type="text" name="skills" value="{{ old('skills') }}" class="form-control"
                               placeholder="e.g. PHP, Laravel, MySQL, Redis, Docker" maxlength="1000">
                        <small class="form-text text-muted">{{ __('Comma-separated. Used for matching candidates to future jobs.') }}</small>
                    </div>

                    {{-- URLs --}}
                    <div class="col-md-6">
                        <label class="form-label">{{ __('LinkedIn URL') }}</label>
                        <input type="url" name="linkedin_url" value="{{ old('linkedin_url') }}" class="form-control" maxlength="500" placeholder="https://linkedin.com/in/...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Portfolio / GitHub URL') }}</label>
                        <input type="url" name="portfolio_url" value="{{ old('portfolio_url') }}" class="form-control" maxlength="500">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">{{ __('Resume (PDF / DOC)') }}</label>
                        <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
                    </div>

                    {{-- Compensation --}}
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Current CTC') }}</label>
                        <input type="number" step="0.01" name="current_ctc" value="{{ old('current_ctc') }}" class="form-control" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Expected CTC') }}</label>
                        <input type="number" step="0.01" name="expected_ctc" value="{{ old('expected_ctc') }}" class="form-control" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Notice Period (days)') }}</label>
                        <input type="number" name="notice_period_days" value="{{ old('notice_period_days') }}" class="form-control" min="0" max="365">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Preferred Locations') }}</label>
                        <input type="text" name="preferred_locations" value="{{ old('preferred_locations') }}" class="form-control" maxlength="500" placeholder="e.g. Mumbai, Bangalore, Remote">
                    </div>

                    {{-- Sourcing --}}
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Source') }}</label>
                        <select name="source" class="form-select">
                            @foreach(\App\Models\TalentPoolCandidate::$sources as $k => $label)
                                <option value="{{ $k }}" @selected(old('source', 'outbound') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Source Detail') }}</label>
                        <input type="text" name="source_detail" value="{{ old('source_detail') }}" class="form-control" maxlength="200" placeholder="e.g. Referred by Rahul S.">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Assigned Recruiter') }}</label>
                        <select name="assigned_recruiter_id" class="form-select">
                            <option value="">{{ __('-- Unassigned --') }}</option>
                            @foreach(\App\Models\User::where('created_by', \Auth::user()->creatorId())->whereIn('type', ['hr','company','employee'])->orderBy('name')->get() as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tags + notes --}}
                    <div class="col-md-12">
                        <label class="form-label">{{ __('Tags') }}</label>
                        <input type="text" name="tags" value="{{ old('tags') }}" class="form-control" maxlength="500"
                               placeholder="e.g. Senior, Diversity, Returning, Top-talent">
                        <small class="form-text text-muted">{{ __('Comma-separated. Used to filter and segment the pool.') }}</small>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" rows="3" class="form-control" maxlength="5000"
                                  placeholder="{{ __('Conversation context, why they are a fit, any follow-up actions…') }}">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('recruitment.talent-pool.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                <button class="btn btn-primary"><i class="ti ti-user-plus me-1"></i>{{ __('Add to Pool') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
