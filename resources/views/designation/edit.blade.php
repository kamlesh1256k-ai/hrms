
{{ Form::model($designation, ['route' => ['designation.update', $designation->id], 'method' => 'PUT', 'class' => 'needs-validation', 'novalidate', 'data-addr-country' => $designation->country ?? '', 'data-addr-state' => $designation->state ?? '', 'data-addr-city' => $designation->city ?? '']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
                {{ Form::label('branch_id', __('Branch'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::select('branch_id', $branchs, null, ['class' => 'form-control ', 'required' => 'required']) }}
                </div>
            </div>
        </div>
        
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
                {{ Form::label('department_id', __('Department'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::select('department_id', $departments, null, ['class' => 'form-control ', 'required' => 'required']) }}
                </div>
            </div>
        </div>

        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
                {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('name', null, ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('Enter Designation Name')]) }}
                </div>
                @error('name')
                    <span class="invalid-name" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">{{ __('Country') }}</label>
                <select name="country" class="form-control addr-country"><option value="">{{ __('Select Country') }}</option></select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">{{ __('State') }}</label>
                <select name="state" class="form-control addr-state"><option value="">{{ __('Select State') }}</option></select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">{{ __('City') }}</label>
                <select name="city" class="form-control addr-city"><option value="">{{ __('Select City') }}</option></select>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
