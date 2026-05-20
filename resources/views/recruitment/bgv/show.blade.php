@extends('layouts.admin')

@section('page-title') {{ __('BGV') }} — {{ $candidate->name }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.bgv.index') }}">{{ __('BGV') }}</a></li>
    <li class="breadcrumb-item">{{ $candidate->name }}</li>
@endsection

@section('content')
<div class="container-fluid">
    @include('recruitment._nav')

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div>   @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">{{ $candidate->name }}</h4>
            <div class="text-muted small">
                {{ $candidate->email }} · {{ $candidate->phone }}
                @if($candidate->jobs)
                    · <strong>{{ $candidate->jobs->title }}</strong>
                @endif
            </div>
        </div>
        @if($checks->isEmpty())
            <form method="POST" action="{{ route('recruitment.bgv.initiate', $candidate->id) }}">
                @csrf
                <button class="btn btn-primary"><i class="ti ti-shield-check me-1"></i>{{ __('Initiate BGV Checklist') }}</button>
            </form>
        @endif
    </div>

    @if($checks->isEmpty())
        <div class="card text-center py-5">
            <div class="card-body text-muted">
                <i class="ti ti-shield-off" style="font-size:3rem;opacity:.4;"></i>
                <p class="mt-2 mb-0">{{ __('No BGV checks initiated yet.') }}</p>
                <small>{{ __('Click "Initiate BGV Checklist" above to seed the standard 8-point checklist.') }}</small>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-lg-8">
                @foreach($checks->groupBy('check_type') as $type => $rows)
                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0 text-capitalize">{{ \App\Models\BgvCheck::$types[$type] ?? $type }}</h6></div>
                        <div class="card-body p-0">
                            @foreach($rows as $row)
                                <div class="px-3 py-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="me-3 flex-grow-1">
                                            <div class="fw-semibold">{{ $row->item_label }}</div>
                                            @if($row->notes)<div class="small text-muted mt-1" style="white-space:pre-wrap;">{{ $row->notes }}</div>@endif
                                            @if($row->document_path)
                                                <a href="{{ asset('storage/'.$row->document_path) }}" target="_blank" class="small">
                                                    <i class="ti ti-paperclip"></i> {{ __('View document') }}
                                                </a>
                                            @endif
                                            @if($row->completed_on)
                                                <div class="small text-muted">{{ __('Completed') }}: {{ $row->completed_on->format('d M Y') }}</div>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-{{ \App\Models\BgvCheck::$statusBadge[$row->status] ?? 'secondary' }} mb-1">
                                                {{ \App\Models\BgvCheck::$statuses[$row->status] }}
                                            </span>
                                            <br>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCheck-{{ $row->id }}"><i class="ti ti-edit"></i></button>
                                            <form method="POST" action="{{ route('recruitment.bgv.delete', $row->id) }}" class="d-inline" onsubmit="return confirm('Delete this check?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button></form>
                                        </div>
                                    </div>
                                </div>

                                {{-- Edit modal per row --}}
                                <div class="modal fade" id="editCheck-{{ $row->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('recruitment.bgv.update', $row->id) }}" enctype="multipart/form-data" class="modal-content">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ $row->item_label }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('Status') }}</label>
                                                    <select name="status" class="form-select" required>
                                                        @foreach(\App\Models\BgvCheck::$statuses as $k => $label)
                                                            <option value="{{ $k }}" @selected($row->status === $k)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('Completion Date') }}</label>
                                                    <input type="date" name="completed_on" value="{{ $row->completed_on?->toDateString() }}" class="form-control">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('Notes') }}</label>
                                                    <textarea name="notes" rows="3" class="form-control" maxlength="2000">{{ $row->notes }}</textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('Supporting Document') }}</label>
                                                    <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                                    @if($row->document_path)
                                                        <small class="text-muted d-block mt-1"><i class="ti ti-paperclip"></i> {{ __('Already attached') }}: <a href="{{ asset('storage/'.$row->document_path) }}" target="_blank">{{ basename($row->document_path) }}</a></small>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                                <button class="btn btn-primary">{{ __('Save') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Add custom check --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0"><i class="ti ti-plus me-1"></i>{{ __('Add Custom Check') }}</h6></div>
                    <form method="POST" action="{{ route('recruitment.bgv.add', $candidate->id) }}" class="card-body">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Type') }}</label>
                            <select name="check_type" class="form-select" required>
                                @foreach(\App\Models\BgvCheck::$types as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Item Label') }}</label>
                            <input type="text" name="item_label" class="form-control" required maxlength="200" placeholder="e.g. Reference from manager Mr. X">
                        </div>
                        <button class="btn btn-primary w-100"><i class="ti ti-plus me-1"></i>{{ __('Add Check') }}</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
