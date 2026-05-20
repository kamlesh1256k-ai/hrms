@extends('layouts.admin')

@section('page-title') {{ __('Raise Manpower Requisition') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.requisitions.index') }}">{{ __('Requisitions') }}</a></li>
    <li class="breadcrumb-item">{{ __('Raise') }}</li>
@endsection

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="ti ti-file-plus me-1"></i>{{ __('Raise Manpower Requisition') }}</h6>
            <p class="text-muted small mb-0 mt-1">{{ __('Fill in the role details and required skills. HR / Management will review and approve.') }}</p>
        </div>
        <form method="POST" action="{{ route('recruitment.requisitions.store') }}">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">{{ __('Job Title') }} <span class="text-danger">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" required maxlength="200" placeholder="e.g. Senior Backend Engineer">
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('Number of Positions') }} <span class="text-danger">*</span></label>
                        <input type="number" name="positions" value="{{ old('positions', 1) }}" class="form-control" required min="1" max="100">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('Department') }}</label>
                        <select name="department_id" class="form-select">
                            <option value="">{{ __('Select') }}</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('Designation') }}</label>
                        <select name="designation_id" class="form-select">
                            <option value="">{{ __('Select') }}</option>
                            @foreach($designations as $d)
                                <option value="{{ $d->id }}" @selected(old('designation_id') == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('Branch / Location') }}</label>
                        <select name="branch_id" class="form-select">
                            <option value="">{{ __('Select') }}</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">{{ __('Required Skills') }} <span class="text-danger">*</span></label>
                        <textarea name="skills" rows="2" class="form-control @error('skills') is-invalid @enderror" required maxlength="1000" placeholder="e.g. PHP, Laravel, MySQL, REST API, Redis">{{ old('skills') }}</textarea>
                        <div class="form-text">{{ __('Comma or newline separated. These skills drive the auto JD generator.') }}</div>
                        @error('skills') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('Experience Required') }}</label>
                        <input type="text" name="experience" value="{{ old('experience') }}" class="form-control" placeholder="e.g. 3-5 years">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('Priority') }} <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            @foreach(\App\Models\ManpowerRequisition::$priorities as $k => $label)
                                <option value="{{ $k }}" @selected(old('priority', 'medium') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">{{ __('Reason') }} <span class="text-danger">*</span></label>
                        <select name="reason" class="form-select" required>
                            @foreach(\App\Models\ManpowerRequisition::$reasons as $k => $label)
                                <option value="{{ $k }}" @selected(old('reason', 'new_hire') === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Replacement For (if applicable)') }}</label>
                        <input type="text" name="replacement_for" value="{{ old('replacement_for') }}" class="form-control" maxlength="200" placeholder="e.g. John Doe (resigned)">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('Salary Range') }}</label>
                        <input type="text" name="salary_range" value="{{ old('salary_range') }}" class="form-control" placeholder="e.g. ₹8-12 LPA">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('Job Type') }}</label>
                        <select name="job_type" class="form-select">
                            <option value="">{{ __('Select') }}</option>
                            @foreach(['Full-time','Part-time','Contract','Internship'] as $opt)
                                <option value="{{ $opt }}" @selected(old('job_type') === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Location') }}</label>
                        <input type="text" name="location" value="{{ old('location') }}" class="form-control" maxlength="200" placeholder="e.g. Mumbai / Remote">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('Needed By') }}</label>
                        <input type="date" name="needed_by" value="{{ old('needed_by') }}" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label">{{ __('Description / Justification') }}</label>
                        <textarea name="description" rows="4" class="form-control" maxlength="5000" placeholder="{{ __('Why is this role needed? Any context for the approver.') }}">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Approval Chain') }}</label>
                        <select name="approval_chain" class="form-select">
                            <option value="hr"          @selected(old('approval_chain') === 'hr')>{{ __('HR only (1 step)') }}</option>
                            <option value="hr,finance"  @selected(old('approval_chain', 'hr,finance') === 'hr,finance')>{{ __('HR → Finance (2 steps)') }}</option>
                            <option value="finance,hr"  @selected(old('approval_chain') === 'finance,hr')>{{ __('Finance → HR (2 steps)') }}</option>
                        </select>
                        <div class="form-text">{{ __('Manager raises the requisition; the chain runs in order before HR can post the job.') }}</div>
                    </div>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('recruitment.requisitions.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                <button type="submit" name="submit_action" value="save_draft" class="btn btn-outline-primary">
                    <i class="ti ti-device-floppy me-1"></i>{{ __('Save Draft') }}
                </button>
                <button type="submit" name="submit_action" value="submit" class="btn btn-primary">
                    <i class="ti ti-send me-1"></i>{{ __('Submit for Approval') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
