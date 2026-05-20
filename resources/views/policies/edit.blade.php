@extends('layouts.admin')
@section('page-title') {{ __('Edit Policy') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('policies.index') }}">{{ __('Policies') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection

@push('css-page')
<style>
    .pol-form .form-label{font-weight:600;font-size:.84rem;color:#334155;}
    .pol-form .help{font-size:.72rem;color:#94a3b8;margin-top:3px;}
    .file-current{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:.85rem;display:flex;align-items:center;gap:8px;}
</style>
@endpush

@section('content')
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('policies.update', $policy->id) }}" class="pol-form" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ti ti-pencil me-2"></i>{{ __('Edit Policy') }}</h5></div>
                    <div class="card-body">

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required maxlength="200"
                                   value="{{ old('title', $policy->title) }}">
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label">{{ __('Category') }} <span class="text-danger">*</span></label>
                                <select name="category" class="form-control" required>
                                    @foreach($categories as $code => $label)
                                        <option value="{{ $code }}" {{ old('category', $policy->category) === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label">{{ __('Version') }}</label>
                                <input type="text" name="version" class="form-control" maxlength="20"
                                       value="{{ old('version', $policy->version) }}">
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" class="form-control" rows="3" maxlength="2000">{{ old('description', $policy->description) }}</textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Current File') }}</label>
                            <div class="file-current">
                                <i class="ti ti-file-text"></i>
                                <strong class="flex-grow-1">{{ $policy->file_name }}</strong>
                                <a href="{{ route('policies.file', $policy->id) }}" target="_blank" class="btn btn-light border btn-sm">
                                    <i class="ti ti-eye me-1"></i>{{ __('Open') }}
                                </a>
                            </div>
                            <div class="help mt-1">{{ __('Upload a new file below to replace it.') }}</div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Replace File (optional)') }}</label>
                            <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.odt,.txt">
                            <div class="help">{{ __('Leave blank to keep the current file.') }}</div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ti ti-settings me-2"></i>{{ __('Settings') }}</h5></div>
                    <div class="card-body">

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" class="form-control">
                                <option value="active"   {{ old('status', $policy->status) === 'active'   ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="archived" {{ old('status', $policy->status) === 'archived' ? 'selected' : '' }}>{{ __('Archived') }}</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_mandatory" value="0">
                                <input class="form-check-input" type="checkbox" id="mand" name="is_mandatory" value="1"
                                       {{ old('is_mandatory', $policy->is_mandatory) ? 'checked' : '' }}>
                                <label class="form-check-label" for="mand">
                                    <strong>{{ __('Mandatory acknowledgement') }}</strong>
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('Save Changes') }}</button>
            <a href="{{ route('policies.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
