@extends('layouts.admin')
@section('page-title') {{ __('Upload Policy') }} @endsection
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('policies.index') }}">{{ __('Policies') }}</a></li>
    <li class="breadcrumb-item">{{ __('Upload') }}</li>
@endsection

@push('css-page')
<style>
    .pol-form .form-label{font-weight:600;font-size:.84rem;color:#334155;}
    .pol-form .help{font-size:.72rem;color:#94a3b8;margin-top:3px;}
    .file-drop{border:2px dashed #cbd5e1;border-radius:10px;padding:24px;text-align:center;background:#fafafa;cursor:pointer;transition:all .12s;}
    .file-drop:hover{border-color:#94a3b8;background:#f1f5f9;}
    .file-drop i{font-size:2rem;color:#94a3b8;display:block;margin-bottom:8px;}
    .file-drop input{display:none;}
    .file-name{display:none;font-weight:600;color:#0f172a;margin-top:8px;}
    .file-name.show{display:block;}
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

    <form method="POST" action="{{ route('policies.store') }}" class="pol-form" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ti ti-upload me-2"></i>{{ __('Policy Details') }}</h5></div>
                    <div class="card-body">

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required maxlength="200"
                                   value="{{ old('title') }}" placeholder="{{ __('e.g. Code of Conduct 2026') }}">
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label">{{ __('Category') }} <span class="text-danger">*</span></label>
                                <select name="category" class="form-control" required>
                                    @foreach($categories as $code => $label)
                                        <option value="{{ $code }}" {{ old('category', 'hr') === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6 mb-3">
                                <label class="form-label">{{ __('Version') }}</label>
                                <input type="text" name="version" class="form-control" maxlength="20"
                                       value="{{ old('version', '1.0') }}" placeholder="1.0">
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" class="form-control" rows="3" maxlength="2000"
                                      placeholder="{{ __('Optional context shown to employees…') }}">{{ old('description') }}</textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Policy File') }} <span class="text-danger">*</span></label>
                            <label class="file-drop" id="fileDrop">
                                <i class="ti ti-cloud-upload"></i>
                                <div><strong>{{ __('Click to choose a file') }}</strong></div>
                                <small class="text-muted">{{ __('PDF, DOC, DOCX, ODT, TXT — up to 10 MB') }}</small>
                                <input type="file" name="file" id="fileInput" accept=".pdf,.doc,.docx,.odt,.txt" required>
                                <div class="file-name" id="fileName"></div>
                            </label>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0"><i class="ti ti-settings me-2"></i>{{ __('Settings') }}</h5></div>
                    <div class="card-body">

                        <div class="form-group mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_mandatory" value="0">
                                <input class="form-check-input" type="checkbox" id="mand" name="is_mandatory" value="1"
                                       {{ old('is_mandatory') ? 'checked' : '' }}>
                                <label class="form-check-label" for="mand">
                                    <strong>{{ __('Mandatory acknowledgement') }}</strong>
                                </label>
                            </div>
                            <div class="help">{{ __('Employees must acknowledge to mark themselves compliant.') }}</div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('Upload Policy') }}</button>
            <a href="{{ route('policies.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection

@push('script-page')
<script>
(function(){
    const input = document.getElementById('fileInput');
    const name  = document.getElementById('fileName');
    if (!input || !name) return;
    input.addEventListener('change', function(){
        if (input.files && input.files[0]) {
            name.textContent = input.files[0].name + ' (' + Math.round(input.files[0].size / 1024) + ' KB)';
            name.classList.add('show');
        } else {
            name.classList.remove('show');
        }
    });
})();
</script>
@endpush
