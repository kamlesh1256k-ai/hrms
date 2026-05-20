{{ Form::model($deductionoption, ['route' => ['deductionoption.update', $deductionoption->id],'method' => 'PUT', 'class' => 'needs-validation', 'novalidate', 'data-addr-country' => $deductionoption->country ?? '', 'data-addr-state' => $deductionoption->state ?? '', 'data-addr-city' => $deductionoption->city ?? '']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12">
            <div class="form-group">
                {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}
                <div class="form-icon-user">
                    {{ Form::text('name', null, ['class' => 'form-control', 'required'=>'required', 'placeholder' => __('Enter Deduction Option Name')]) }}
                </div>
                @error('name')
                    <span class="invalid-name" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="col-md-4"><div class="form-group"><label class="form-label">{{ __('Country') }}</label><select name="country" class="form-control addr-country"><option value="">{{ __('Select Country') }}</option></select></div></div>
        <div class="col-md-4"><div class="form-group"><label class="form-label">{{ __('State') }}</label><select name="state" class="form-control addr-state"><option value="">{{ __('Select State') }}</option></select></div></div>
        <div class="col-md-4"><div class="form-group"><label class="form-label">{{ __('City') }}</label><select name="city" class="form-control addr-city"><option value="">{{ __('Select City') }}</option></select></div></div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="Cancel" class="btn btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}
