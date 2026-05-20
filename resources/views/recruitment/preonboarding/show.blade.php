@extends('layouts.admin')

@section('page-title') {{ __('Pre-Onboarding') }} — {{ $candidate->name }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('recruitment.dashboard') }}">{{ __('Recruitment') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('recruitment.preonboarding.index') }}">{{ __('Pre-Onboarding') }}</a></li>
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
        @if($items->isEmpty())
            <form method="POST" action="{{ route('recruitment.preonboarding.initiate', $candidate->id) }}">
                @csrf
                <button class="btn btn-primary"><i class="ti ti-checklist me-1"></i>{{ __('Initiate Pre-Onboarding Checklist') }}</button>
            </form>
        @endif
    </div>

    @if($items->isEmpty())
        <div class="card text-center py-5">
            <div class="card-body text-muted">
                <i class="ti ti-clipboard-off" style="font-size:3rem;opacity:.4;"></i>
                <p class="mt-2 mb-0">{{ __('No pre-onboarding items yet.') }}</p>
                <small>{{ __('Click "Initiate Pre-Onboarding Checklist" to seed the standard 15-item checklist.') }}</small>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-lg-8">
                @foreach($items->groupBy('category') as $cat => $rows)
                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0 text-capitalize">{{ \App\Models\PreonboardingItem::$categories[$cat] ?? $cat }}</h6></div>
                        <div class="card-body p-0">
                            @foreach($rows as $item)
                                <div class="px-3 py-2 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="me-3 flex-grow-1">
                                            <div class="fw-semibold">
                                                @if($item->status === 'completed') <i class="ti ti-circle-check text-success"></i>
                                                @elseif($item->status === 'waived') <i class="ti ti-circle-minus text-secondary"></i>
                                                @else <i class="ti ti-circle text-muted"></i>
                                                @endif
                                                {{ $item->item_label }}
                                            </div>
                                            @if($item->notes)<div class="small text-muted mt-1" style="white-space:pre-wrap;">{{ $item->notes }}</div>@endif
                                            @if($item->document_path)
                                                <a href="{{ asset('storage/'.$item->document_path) }}" target="_blank" class="small">
                                                    <i class="ti ti-paperclip"></i> {{ __('View document') }}
                                                </a>
                                            @endif
                                            @if($item->due_by)
                                                <div class="small text-muted">{{ __('Due by') }}: {{ $item->due_by->format('d M Y') }}</div>
                                            @endif
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-{{ \App\Models\PreonboardingItem::$statusBadge[$item->status] ?? 'secondary' }} mb-1">
                                                {{ \App\Models\PreonboardingItem::$statuses[$item->status] }}
                                            </span>
                                            <br>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editItem-{{ $item->id }}"><i class="ti ti-edit"></i></button>
                                            <form method="POST" action="{{ route('recruitment.preonboarding.delete', $item->id) }}" class="d-inline" onsubmit="return confirm('Delete this item?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button></form>
                                        </div>
                                    </div>
                                </div>

                                <div class="modal fade" id="editItem-{{ $item->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('recruitment.preonboarding.update', $item->id) }}" enctype="multipart/form-data" class="modal-content">
                                            @csrf
                                            <div class="modal-header"><h5 class="modal-title">{{ $item->item_label }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('Status') }}</label>
                                                    <select name="status" class="form-select" required>
                                                        @foreach(\App\Models\PreonboardingItem::$statuses as $k => $label)
                                                            <option value="{{ $k }}" @selected($item->status === $k)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('Due By') }}</label>
                                                    <input type="date" name="due_by" value="{{ $item->due_by?->toDateString() }}" class="form-control">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('Notes') }}</label>
                                                    <textarea name="notes" rows="3" class="form-control" maxlength="2000">{{ $item->notes }}</textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('Attach Document') }}</label>
                                                    <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                                    @if($item->document_path)
                                                        <small class="text-muted d-block mt-1"><i class="ti ti-paperclip"></i> <a href="{{ asset('storage/'.$item->document_path) }}" target="_blank">{{ basename($item->document_path) }}</a></small>
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

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0"><i class="ti ti-plus me-1"></i>{{ __('Add Custom Item') }}</h6></div>
                    <form method="POST" action="{{ route('recruitment.preonboarding.add', $candidate->id) }}" class="card-body">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Category') }}</label>
                            <select name="category" class="form-select" required>
                                @foreach(\App\Models\PreonboardingItem::$categories as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Item Label') }}</label>
                            <input type="text" name="item_label" class="form-control" required maxlength="200" placeholder="e.g. NDA signed">
                        </div>
                        <button class="btn btn-primary w-100"><i class="ti ti-plus me-1"></i>{{ __('Add Item') }}</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
