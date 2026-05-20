@extends('layouts.admin')

@section('page-title')
    {{ __('Create Employee') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ url('employee') }}">{{ __('Employee') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create Employee') }}</li>
@endsection

@push('css')
    <style>
        .cursor-pointer {
            cursor: pointer;
        }

        .passport-preview {
            width: 90px;
            height: 110px;
            object-fit: cover;
            border: 1px solid #d9d9d9;
            border-radius: 6px;
            margin-top: 8px;
            display: block;
        }

        .document-card-body {
            height: auto !important;
            min-height: 0 !important;
            max-height: none !important;
            overflow: visible !important;
            padding-bottom: 12px !important;
        }

        .document-card {
            height: auto !important;
            min-height: 0 !important;
        }

        .document-card-body .form-group {
            margin-bottom: 8px !important;
        }

        .document-card-body .choose-files {
            margin-bottom: 0;
        }

        .document-card-body .document {
            padding: 6px 10px !important;
            font-size: 13px !important;
            min-height: auto !important;
            line-height: 1.2 !important;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .document-card-body .file.form-control {
            height: 34px !important;
            font-size: 13px !important;
            padding: 4px 8px !important;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="">
            <div class="">
                <div class="row">

                </div>
                {{ Form::open(['route' => ['employee.store'], 'method' => 'post', 'enctype' => 'multipart/form-data', 'class' => 'needs-validation', 'novalidate']) }}
                @if (session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="row">
                    <div class="col-md-6">
                        <div class="card em-card document-card">
                            <div class="card-header">
                                <h5>{{ __('Personal Detail') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        {!! Form::label('name', __('Name'), ['class' => 'form-label']) !!}<x-required></x-required>
                                        {!! Form::text('name', old('name'), [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                            'placeholder' => 'Enter employee name',
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('phone', __('Phone'), ['class' => 'form-label']) !!}<x-required></x-required>
                                        {!! Form::text('phone', old('phone'), [
                                            'class' => 'form-control',
                                            'placeholder' => __('Enter employee phone'),
                                            'id' => 'phone',
                                            'required' => 'required',
                                        ]) !!}
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('dob', __('Date of Birth'), ['class' => 'form-label']) !!}<x-required></x-required>
                                            {{ Form::date('dob', null, ['class' => 'form-control', 'required' => 'required', 'autocomplete' => 'off', 'placeholder' => 'Select Date of Birth', 'max' => date('Y-m-d')]) }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('gender', __('Gender'), ['class' => 'form-label']) !!}<x-required></x-required>
                                            <div class="d-flex radio-check">
                                                <div class="custom-control custom-radio custom-control-inline">
                                                    <input type="radio" id="g_male" value="Male" name="gender"
                                                        class="form-check-input" checked="checked">
                                                    <label class="form-check-label "
                                                        for="g_male">{{ __('Male') }}</label>
                                                </div>
                                                <div class="custom-control custom-radio ms-1 custom-control-inline">
                                                    <input type="radio" id="g_female" value="Female" name="gender"
                                                        class="form-check-input">
                                                    <label class="form-check-label "
                                                        for="g_female">{{ __('Female') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('email', __('Email'), ['class' => 'form-label']) !!}<x-required></x-required>
                                        {!! Form::email('email', old('email'), [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                            'placeholder' => __('Enter employee email'),
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('password', __('Password'), ['class' => 'form-label']) !!}<x-required></x-required>
                                        {!! Form::password('password', [
                                            'class' => 'form-control',
                                            'required' => 'required',
                                            'placeholder' => __('Enter employee password'),
                                        ]) !!}
                                    </div>
                                </div>
                                <div class="form-group">
                                    {!! Form::label('present_address', __('Present Address'), ['class' => 'form-label']) !!}<x-required></x-required>
                                    {!! Form::textarea('present_address', old('present_address', old('address')), [
                                        'class' => 'form-control',
                                        'rows' => 2,
                                        'required' => 'required',
                                        'id' => 'present_address',
                                        'placeholder' => __('Enter address line'),
                                    ]) !!}
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        {!! Form::label('present_country', __('Country'), ['class' => 'form-label']) !!}
                                        <select name="present_country" id="present_country" class="form-control">
                                            <option value="">{{ __('Select Country') }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('present_state', __('State'), ['class' => 'form-label']) !!}
                                        <select name="present_state" id="present_state" class="form-control">
                                            <option value="">{{ __('Select State') }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('present_city', __('City'), ['class' => 'form-label']) !!}
                                        <select name="present_city" id="present_city" class="form-control">
                                            <option value="">{{ __('Select City') }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('present_pincode', __('Pincode'), ['class' => 'form-label']) !!}
                                        {!! Form::text('present_pincode', old('present_pincode'), [
                                            'class' => 'form-control',
                                            'id' => 'present_pincode',
                                            'placeholder' => __('Pincode'),
                                            'maxlength' => 20,
                                        ]) !!}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="same_as_present">
                                        <label class="form-check-label cursor-pointer" for="same_as_present">
                                            {{ __('Permanent address same as present (click to copy)') }}
                                        </label>
                                    </div>
                                    {!! Form::label('permanent_address', __('Permanent Address'), ['class' => 'form-label']) !!}<x-required></x-required>
                                    {!! Form::textarea('permanent_address', old('permanent_address'), [
                                        'class' => 'form-control',
                                        'rows' => 2,
                                        'required' => 'required',
                                        'id' => 'permanent_address',
                                        'placeholder' => __('Enter address line'),
                                    ]) !!}
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        {!! Form::label('permanent_country', __('Country'), ['class' => 'form-label']) !!}
                                        <select name="permanent_country" id="permanent_country" class="form-control">
                                            <option value="">{{ __('Select Country') }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('permanent_state', __('State'), ['class' => 'form-label']) !!}
                                        <select name="permanent_state" id="permanent_state" class="form-control">
                                            <option value="">{{ __('Select State') }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('permanent_city', __('City'), ['class' => 'form-label']) !!}
                                        <select name="permanent_city" id="permanent_city" class="form-control">
                                            <option value="">{{ __('Select City') }}</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('permanent_pincode', __('Pincode'), ['class' => 'form-label']) !!}
                                        {!! Form::text('permanent_pincode', old('permanent_pincode'), [
                                            'class' => 'form-control',
                                            'id' => 'permanent_pincode',
                                            'placeholder' => __('Pincode'),
                                            'maxlength' => 20,
                                        ]) !!}
                                    </div>
                                </div>
                                {!! Form::hidden('address', old('present_address', old('address')), ['id' => 'legacy_address']) !!}
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        {!! Form::label('blood_group', __('Blood Group'), ['class' => 'form-label']) !!}
                                        {!! Form::select('blood_group', [
                                            'A+' => 'A+',
                                            'A-' => 'A-',
                                            'B+' => 'B+',
                                            'B-' => 'B-',
                                            'AB+' => 'AB+',
                                            'AB-' => 'AB-',
                                            'O+' => 'O+',
                                            'O-' => 'O-',
                                        ], old('blood_group'), ['class' => 'form-control', 'placeholder' => __('Select Blood Group')]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('food_type', __('Food Type'), ['class' => 'form-label']) !!}
                                        {!! Form::select('food_type', [
                                            'Veg' => 'Veg',
                                            'Vegan' => 'Vegan',
                                            'Non-Veg' => 'Non-Veg',
                                        ], old('food_type'), ['class' => 'form-control', 'placeholder' => __('Select Food Type')]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('emergency_contact_name', __('Emergency Contact Name'), ['class' => 'form-label']) !!}
                                        {!! Form::text('emergency_contact_name', old('emergency_contact_name'), [
                                            'class' => 'form-control',
                                            'placeholder' => __('Enter emergency contact name'),
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('emergency_contact_phone', __('Emergency Contact Phone'), ['class' => 'form-label']) !!}
                                        {!! Form::text('emergency_contact_phone', old('emergency_contact_phone'), [
                                            'class' => 'form-control',
                                            'placeholder' => __('Enter emergency contact phone'),
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('insurance_id', __('Insurance ID'), ['class' => 'form-label']) !!}
                                        {!! Form::text('insurance_id', old('insurance_id'), [
                                            'class' => 'form-control',
                                            'placeholder' => __('Enter insurance ID'),
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('insurer_name', __('Name of Insurer'), ['class' => 'form-label']) !!}
                                        {!! Form::text('insurer_name', old('insurer_name'), [
                                            'class' => 'form-control',
                                            'placeholder' => __('Enter insurer name'),
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-12">
                                        {!! Form::label('insurance_contact_person', __('Insurance Contact Person'), ['class' => 'form-label']) !!}
                                        {!! Form::text('insurance_contact_person', old('insurance_contact_person'), [
                                            'class' => 'form-control',
                                            'placeholder' => __('Enter insurance contact person'),
                                        ]) !!}
                                    </div>
                                    <div class="col-md-12">
                                        <h6 class="mb-2">{{ __('Family Details') }}</h6>
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('family_father_name', __('Father Name'), ['class' => 'form-label']) !!}
                                        {!! Form::text('family_father_name', old('family_father_name'), ['class' => 'form-control', 'placeholder' => __('Enter father name')]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('family_mother_name', __('Mother Name'), ['class' => 'form-label']) !!}
                                        {!! Form::text('family_mother_name', old('family_mother_name'), ['class' => 'form-control', 'placeholder' => __('Enter mother name')]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('family_spouse_name', __('Spouse Name'), ['class' => 'form-label']) !!}
                                        {!! Form::text('family_spouse_name', old('family_spouse_name'), ['class' => 'form-control', 'placeholder' => __('Enter spouse name')]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('family_children_count', __('No. of Children'), ['class' => 'form-label']) !!}
                                        {!! Form::number('family_children_count', old('family_children_count'), ['class' => 'form-control', 'min' => 0, 'placeholder' => __('Enter number of children')]) !!}
                                    </div>

                                    <div class="col-md-12 mt-2">
                                        <h6 class="mb-2">{{ __('Hobbies') }}</h6>
                                    </div>
                                    <div class="form-group col-md-4">
                                        {!! Form::label('hobby_indoor', __('Indoor Hobby'), ['class' => 'form-label']) !!}
                                        {!! Form::text('hobby_indoor', old('hobby_indoor'), ['class' => 'form-control', 'placeholder' => __('e.g. Chess')]) !!}
                                    </div>
                                    <div class="form-group col-md-4">
                                        {!! Form::label('hobby_outdoor', __('Outdoor Hobby'), ['class' => 'form-label']) !!}
                                        {!! Form::text('hobby_outdoor', old('hobby_outdoor'), ['class' => 'form-control', 'placeholder' => __('e.g. Cricket')]) !!}
                                    </div>
                                    <div class="form-group col-md-4">
                                        {!! Form::label('hobby_other', __('Other Hobby'), ['class' => 'form-label']) !!}
                                        {!! Form::text('hobby_other', old('hobby_other'), ['class' => 'form-control', 'placeholder' => __('Other hobbies')]) !!}
                                    </div>

                                    <div class="col-md-12 mt-2">
                                        <h6 class="mb-2">{{ __('Education') }}</h6>
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('education_qualification', __('Highest Qualification'), ['class' => 'form-label']) !!}
                                        {!! Form::text('education_qualification', old('education_qualification'), ['class' => 'form-control', 'placeholder' => __('e.g. B.Tech, MBA')]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('education_specialization', __('Specialization'), ['class' => 'form-label']) !!}
                                        {!! Form::text('education_specialization', old('education_specialization'), ['class' => 'form-control', 'placeholder' => __('e.g. Computer Science')]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('education_institute', __('Institute / University'), ['class' => 'form-label']) !!}
                                        {!! Form::text('education_institute', old('education_institute'), ['class' => 'form-control', 'placeholder' => __('Enter institute name')]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('education_passing_year', __('Passing Year'), ['class' => 'form-label']) !!}
                                        {!! Form::text('education_passing_year', old('education_passing_year'), ['class' => 'form-control', 'placeholder' => __('e.g. 2022')]) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card em-card">
                            <div class="card-header">
                                <h5>{{ __('Company Detail') }}</h5>
                            </div>
                            <div class="card-body employee-detail-create-body">
                                <div class="row">
                                    @csrf
                                    <div class="form-group ">
                                        {!! Form::label('employee_id', __('Employee ID'), ['class' => 'form-label']) !!}
                                        {!! Form::text('employee_id', $employeesId, ['class' => 'form-control', 'disabled' => 'disabled']) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {{ Form::label('branch_id', __('Select Branch'), ['class' => 'form-label']) }}<x-required></x-required>
                                        {{ Form::select('branch_id', $branches, null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Select Branch')]) }}
                                        @if (empty($branches->count()))
                                            <div class="text-xs">
                                                {{ __('Please add Branch. ') }}<a
                                                    href="{{ route('branch.index') }}"><b>{{ __('Add Branch') }}</b></a>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="form-group col-md-6">
                                        {{ Form::label('department_id', __('Select Department'), ['class' => 'form-label']) }}<x-required></x-required>
                                        {{ Form::select('department_id', [], null, ['class' => 'form-control', 'id' => 'department_id', 'required' => 'required', 'placeholder' => __('Select Department')]) }}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {{ Form::label('designation_id', __('Select Designation'), ['class' => 'form-label']) }}<x-required></x-required>
                                        {{ Form::select('designation_id', [], null, ['class' => 'form-control', 'id' => 'designation_id', 'required' => 'required', 'placeholder' => __('Select Designation')]) }}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {{ Form::label('department_hierarchy', __('Department Hierarchy'), ['class' => 'form-label']) }}
                                        {{ Form::select('department_hierarchy', ['Employee' => 'Employee', 'Team Member' => 'Team Member', 'Manager' => 'Manager', 'HOD' => 'HOD'], old('department_hierarchy'), ['class' => 'form-control', 'placeholder' => __('Select Hierarchy')]) }}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {{ Form::label('reporting_manager_id', __('Reporting Manager'), ['class' => 'form-label']) }}
                                        {{ Form::select('reporting_manager_id', $managerEmployees ?? [], old('reporting_manager_id'), ['class' => 'form-control', 'placeholder' => __('Select Reporting Manager')]) }}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {{ Form::label('hod_id', __('HOD'), ['class' => 'form-label']) }}
                                        {{ Form::select('hod_id', $hodEmployees ?? [], old('hod_id'), ['class' => 'form-control', 'placeholder' => __('Select HOD')]) }}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {{ Form::label('management_id', __('Management'), ['class' => 'form-label']) }}
                                        {{ Form::select('management_id', $managementEmployees ?? [], old('management_id'), ['class' => 'form-control', 'placeholder' => __('Select Management')]) }}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {{ Form::label('shift_id', __('Shift'), ['class' => 'form-label']) }}
                                        {{ Form::select('shift_id', $shifts ?? [], old('shift_id'), ['class' => 'form-control', 'placeholder' => __('Select Shift')]) }}
                                        {{ Form::hidden('shift_type', 'morning') }}
                                    </div>

                                    {{-- <div class="form-group col-md-6">
                                        {!! Form::label('biometric_emp_id', __('Employee Code'), ['class' => 'form-label']) !!}<x-required></x-required>
                                        {!! Form::text('biometric_emp_id', old('biometric_emp_id'), [
                                            'class' => 'form-control',
                                            'placeholder' => 'Enter Employee Code',
                                            'required' => 'required',
                                        ]) !!}
                                    </div> --}}
                                    <div class="form-group">
                                        {!! Form::label('company_doj', __('Company Date Of Joining'), ['class' => '  form-label']) !!}<x-required></x-required>
                                        {{ Form::date('company_doj', null, ['class' => 'form-control current_date', 'required' => 'required', 'autocomplete' => 'off', 'placeholder' => 'Select company date of joining']) }}
                                    </div>

                                    <div class="form-group">
                                        <label for="employee_type_id" class="form-label">{{ __('Employee Type') }}</label><x-required></x-required>
                                        <select name="employee_type_id" id="employee_type_id" class="form-control" required>
                                            <option value="">{{ __('Select Employee Type') }}</option>
                                            @foreach($employeeTypes ?? [] as $type)
                                                <option value="{{ $type->id }}" data-code="{{ $type->code }}"
                                                    {{ old('employee_type_id', $type->code === 'full_time' ? $type->id : null) == $type->id ? 'selected' : '' }}>
                                                    {{ $type->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1">
                                            {{ __('Determines payroll rules: statutory deductions, CTC vs stipend, etc.') }}
                                        </small>
                                    </div>

                                    <div class="form-group" id="monthly_stipend_group" style="display:none;">
                                        {!! Form::label('monthly_stipend', __('Monthly Stipend'), ['class' => 'form-label']) !!}
                                        {!! Form::number('monthly_stipend', null, [
                                            'class' => 'form-control',
                                            'min' => 0,
                                            'step' => '0.01',
                                            'placeholder' => __('e.g. 15000'),
                                        ]) !!}
                                        <small class="text-muted d-block mt-1">
                                            {{ __('For Intern type. Prorated by attendance each month.') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Save bar after Company Detail --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card" style="border:1px dashed #0d9488; background:#f0fdfa;">
                            <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div style="font-size:.85rem;color:#0f766e;">
                                    <i class="ti ti-info-circle"></i>
                                    {{ __('You can save now with just Personal & Company details. Add documents, bank info etc. later by editing the employee.') }}
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" style="background:#0d9488;border-color:#0d9488;">
                                        <i class="ti ti-device-floppy"></i> {{ __('Save Employee') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 ">
                        <div class="card em-card">
                            <div class="card-header">
                                <h5>{{ __('Document') }}</h6>
                            </div>
                            <div class="card-body document-card-body">
                                @foreach ($documents as $key => $document)
                                    <div class="row">
                                        <div class="form-group col-12 d-flex">
                                            <div class="float-left col-4">
                                                <label for="document"
                                                    class="float-left pt-1 form-label">{{ $document->name }} @if ($document->is_required == 1)
                                                    <x-required></x-required>
                                                    @endif
                                                </label>
                                            </div>
                                            <div class="float-right col-8">
                                                <input type="hidden" name="emp_doc_id[{{ $document->id }}]" id=""
                                                    value="{{ $document->id }}">
                                                <div class="choose-files">
                                                    <label for="document[{{ $document->id }}]">
                                                        <div class=" bg-primary document cursor-pointer"> <i
                                                                class="ti ti-upload "></i>{{ __('Choose file here') }}
                                                        </div>
                                                        <input type="file"
                                                            class="form-control file @error('document') is-invalid @enderror"
                                                            @if ($document->is_required == 1) required @endif
                                                            name="document[{{ $document->id }}]"
                                                            id="document[{{ $document->id }}]"
                                                            data-filename="{{ $document->id . '_filename' }}"
                                                            onchange="document.getElementById('{{ 'blah' . $key }}').src = window.URL.createObjectURL(this.files[0])">
                                                    </label>
                                                    <img id="{{ 'blah' . $key }}" src="" class="passport-preview" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 ">
                        <div class="card em-card">
                            <div class="card-header">
                                <h5>{{ __('Bank Account Detail') }}</h5>
                            </div>
                            <div class="card-body employee-detail-create-body">
                                <div class="row">
                                    <div class="form-group col-md-6">
                                        {!! Form::label('account_holder_name', __('Account Holder Name'), ['class' => 'form-label']) !!}
                                        {!! Form::text('account_holder_name', old('account_holder_name'), [
                                            'class' => 'form-control',
                                            'placeholder' => __('Enter account holder name'),
                                        ]) !!}

                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('account_number', __('Account Number'), ['class' => 'form-label']) !!}
                                        {!! Form::number('account_number', old('account_number'), [
                                            'class' => 'form-control',
                                            'placeholder' => __('Enter account number'),
                                        ]) !!}

                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('bank_name', __('Bank Name'), ['class' => 'form-label']) !!}
                                        {!! Form::text('bank_name', old('bank_name'), ['class' => 'form-control', 'placeholder' => __('Enter bank name')]) !!}

                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('bank_identifier_code', __('Bank Identifier Code'), ['class' => 'form-label']) !!}
                                        {!! Form::text('bank_identifier_code', old('bank_identifier_code'), [
                                            'class' => 'form-control',
                                            'placeholder' => __('Enter bank identifier code'),
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('branch_location', __('Branch Location'), ['class' => 'form-label']) !!}
                                        {!! Form::text('branch_location', old('branch_location'), [
                                            'class' => 'form-control',
                                            'placeholder' => __('Enter branch location'),
                                        ]) !!}
                                    </div>
                                    <div class="form-group col-md-6">
                                        {!! Form::label('tax_payer_id', __('Tax Payer Id'), ['class' => 'form-label']) !!}
                                        {!! Form::text('tax_payer_id', old('tax_payer_id'), [
                                            'class' => 'form-control',
                                            'placeholder' => __('Enter tax payer id'),
                                        ]) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="float-end">
                <a class="btn btn-secondary btn-submit" href="{{ route('employee.index') }}">{{ __('Cancel') }}</a>
                <button class="btn btn-primary btn-submit ms-1" type="submit"
                    id="submit">{{ __('Create') }}</button>
            </div>
            </form>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        $('input[type="file"]').change(function(e) {
            var file = e.target.files[0].name;
            var file_name = $(this).attr('data-filename');
            $('.' + file_name).append(file);
        });
    </script>
    <script type="text/javascript">
        $(document).on('change', '#branch_id', function() {
            var branch_id = $(this).val();
            getDepartment(branch_id);
        });

        function getDepartment(branch_id) {
            var data = {
                "branch_id": branch_id,
                "_token": "{{ csrf_token() }}",
            }

            $.ajax({
                url: '{{ route('monthly.getdepartment') }}',
                method: 'POST',
                data: data,
                success: function(data) {
                    $('#department_id').empty();
                    $('#department_id').append(
                        '<option value="" disabled>{{ __('Select Department') }}</option>');

                    $.each(data, function(key, value) {
                        $('#department_id').append('<option value="' + key + '">' + value +
                            '</option>');
                    });
                    $('#department_id').val('');
                }
            });
        }

        $(document).on('change', 'select[name=department_id]', function() {
            var department_id = $(this).val();
            getDesignation(department_id);
        });

        function getDesignation(did) {
            $.ajax({
                url: '{{ route('employee.json') }}',
                type: 'POST',
                data: {
                    "department_id": did,
                    "_token": "{{ csrf_token() }}",
                },
                success: function(data) {
                    $('#designation_id').empty();
                    $('#designation_id').append(
                        '<option value="">{{ __('Select Designation') }}</option>');
                    $.each(data, function(key, value) {
                        $('#designation_id').append('<option value="' + key + '">' + value +
                            '</option>');
                    });
                }
            });
        }

        // ── Employee Type: show stipend field only for 'intern' ───────────
        function toggleStipendField() {
            var $sel = $('#employee_type_id');
            var code = $sel.find('option:selected').data('code') || '';
            if (code === 'intern') {
                $('#monthly_stipend_group').show();
            } else {
                $('#monthly_stipend_group').hide();
            }
        }
        $(document).on('change', '#employee_type_id', toggleStipendField);
        $(function(){ toggleStipendField(); });

        var employeeAddress = {
            present_country: "{{ old('present_country', '') }}",
            present_state: "{{ old('present_state', '') }}",
            present_city: "{{ old('present_city', '') }}",
            permanent_country: "{{ old('permanent_country', '') }}",
            permanent_state: "{{ old('permanent_state', '') }}",
            permanent_city: "{{ old('permanent_city', '') }}"
        };
        var addressXhr = {
            states: { present: null, permanent: null },
            cities: { present: null, permanent: null }
        };

        function normalizeValue(value) {
            return (value || '').toString().trim().toLowerCase();
        }

        function selectByValueOrText(selector, expected) {
            var expectedValue = normalizeValue(expected);
            if (!expectedValue) return '';

            var $select = $(selector);
            var matched = '';
            $select.find('option').each(function() {
                var optionValue = normalizeValue($(this).val());
                var optionText = normalizeValue($(this).text());
                if (expectedValue === optionValue || expectedValue === optionText) {
                    matched = $(this).val();
                    return false;
                }
            });

            if (matched !== '') {
                $select.val(matched);
            }
            return matched;
        }

        function copyPresentToPermanent() {
            $('#permanent_address').val($('#present_address').val());
            $('#legacy_address').val($('#present_address').val());
            $('#permanent_country').val($('#present_country').val());
            $('#permanent_state').val($('#present_state').val());
            $('#permanent_city').val($('#present_city').val());
            $('#permanent_pincode').val($('#present_pincode').val());
            if ($('#present_country').val()) {
                loadStates('permanent', $('#present_country').val(), function() {
                    $('#permanent_state').val($('#present_state').val());
                    loadCities('permanent', $('#present_state').val(), $('#present_country').val(), function() {
                        $('#permanent_city').val($('#present_city').val());
                    });
                });
            }
        }

        function syncPermanentAddressState() {
            const sameAsPresent = document.getElementById('same_as_present');
            const presentAddress = document.getElementById('present_address');
            const permanentAddress = document.getElementById('permanent_address');
            const legacyAddress = document.getElementById('legacy_address');

            if (!presentAddress || !permanentAddress || !sameAsPresent) return;
            legacyAddress.value = presentAddress.value;

            if (sameAsPresent.checked) {
                copyPresentToPermanent();
                permanentAddress.setAttribute('readonly', 'readonly');
                $('#permanent_pincode').prop('readonly', true).addClass('bg-light');
                $('#permanent_country, #permanent_state, #permanent_city').addClass('bg-light');
            } else {
                permanentAddress.removeAttribute('readonly');
                $('#permanent_pincode').prop('readonly', false).removeClass('bg-light');
                $('#permanent_country, #permanent_state, #permanent_city').removeClass('bg-light');
            }
        }

        $(document).on('change', '#same_as_present', function() {
            syncPermanentAddressState();
        });

        $(document).on('input', '#present_address', function() {
            if (document.getElementById('same_as_present').checked) {
                $('#permanent_address').val($(this).val());
                document.getElementById('legacy_address').value = $(this).val();
            }
        });

        function loadCountries(done) {
            $.get('{{ route("employee.address.countries") }}', function(res) {
                var opts = '<option value="">{{ __("Select Country") }}</option>';
                (res.countries || []).forEach(function(c) {
                    opts += '<option value="' + c.id + '">' + c.name + '</option>';
                });
                $('#present_country, #permanent_country').html(opts);
                if (done) done();
            });
        }

        function loadStates(prefix, countryId, done) {
            if (!countryId) {
                $('#' + prefix + '_state').html('<option value="">{{ __("Select State") }}</option>');
                $('#' + prefix + '_city').html('<option value="">{{ __("Select City") }}</option>');
                if (done) done();
                return;
            }

            if (addressXhr.states[prefix]) {
                addressXhr.states[prefix].abort();
            }

            var requestedCountry = countryId;
            addressXhr.states[prefix] = $.get('{{ route("employee.address.states") }}', { country_id: countryId }, function(res) {
                if (($('#' + prefix + '_country').val() || '') !== requestedCountry) {
                    return;
                }
                var opts = '<option value="">{{ __("Select State") }}</option>';
                (res.states || []).forEach(function(s) {
                    opts += '<option value="' + s.id + '">' + s.name + '</option>';
                });
                $('#' + prefix + '_state').html(opts);
                $('#' + prefix + '_city').html('<option value="">{{ __("Select City") }}</option>');
                if (done) done();
            }).always(function() {
                addressXhr.states[prefix] = null;
            });
        }

        function loadCities(prefix, stateId, countryId, done) {
            if (!stateId && !countryId) {
                $('#' + prefix + '_city').html('<option value="">{{ __("Select City") }}</option>');
                if (done) done();
                return;
            }

            if (addressXhr.cities[prefix]) {
                addressXhr.cities[prefix].abort();
            }

            var requestedState = stateId || '';
            var requestedCountry = countryId || '';
            addressXhr.cities[prefix] = $.get('{{ route("employee.address.cities") }}', { state_id: requestedState, country_id: requestedCountry }, function(res) {
                if (($('#' + prefix + '_state').val() || '') !== requestedState && requestedState !== '') {
                    return;
                }
                if (($('#' + prefix + '_country').val() || '') !== requestedCountry && requestedCountry !== '') {
                    return;
                }
                var opts = '<option value="">{{ __("Select City") }}</option>';
                (res.cities || []).forEach(function(c) {
                    opts += '<option value="' + c.id + '">' + c.name + '</option>';
                });
                $('#' + prefix + '_city').html(opts);
                if (done) done();
            }).always(function() {
                addressXhr.cities[prefix] = null;
            });
        }

        $('#present_country').on('change', function() {
            loadStates('present', $(this).val());
            if (document.getElementById('same_as_present').checked) copyPresentToPermanent();
        });
        $('#present_state').on('change', function() {
            loadCities('present', $(this).val(), $('#present_country').val());
            if (document.getElementById('same_as_present').checked) copyPresentToPermanent();
        });
        $('#present_city').on('change', function() {
            if (document.getElementById('same_as_present').checked) {
                $('#permanent_city').val($(this).val());
                $('#permanent_pincode').val($('#present_pincode').val());
            }
        });
        $('#permanent_country').on('change', function() {
            loadStates('permanent', $(this).val());
        });
        $('#permanent_state').on('change', function() {
            loadCities('permanent', $(this).val(), $('#permanent_country').val());
        });

        $(document).on('input', '#present_pincode', function() {
            if (document.getElementById('same_as_present').checked) {
                $('#permanent_pincode').val($(this).val());
            }
        });

        loadCountries(function() {
            var presentCountry = selectByValueOrText('#present_country', employeeAddress.present_country) || employeeAddress.present_country;
            var permanentCountry = selectByValueOrText('#permanent_country', employeeAddress.permanent_country) || employeeAddress.permanent_country;

            loadStates('present', presentCountry, function() {
                var presentState = selectByValueOrText('#present_state', employeeAddress.present_state) || employeeAddress.present_state;
                loadCities('present', presentState, presentCountry, function() {
                    selectByValueOrText('#present_city', employeeAddress.present_city);
                });
            });

            loadStates('permanent', permanentCountry, function() {
                var permanentState = selectByValueOrText('#permanent_state', employeeAddress.permanent_state) || employeeAddress.permanent_state;
                loadCities('permanent', permanentState, permanentCountry, function() {
                    selectByValueOrText('#permanent_city', employeeAddress.permanent_city);
                });
            });

            syncPermanentAddressState();
        });
    </script>
@endpush
