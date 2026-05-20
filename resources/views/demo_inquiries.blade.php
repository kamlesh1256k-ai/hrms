@extends('layouts.admin')

@section('page-title', __('Demo Inquiries'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Demo Inquiries') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0"><i class="ti ti-rocket me-2 text-primary"></i>{{ __('Free Demo Inquiries') }}</h5>
                <span class="badge bg-primary">{{ $requests->total() }} {{ __('Total') }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Company') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Phone') }}</th>
                                <th>{{ __('Strength') }}</th>
                                <th>{{ __('Industry') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                            <tr>
                                <td>{{ $req->id }}</td>
                                <td><strong>{{ $req->name }}</strong></td>
                                <td>{{ $req->company }}</td>
                                <td><a href="mailto:{{ $req->email }}">{{ $req->email }}</a></td>
                                <td>{{ $req->phone ?: '—' }}</td>
                                <td>{{ $req->strength }}</td>
                                <td>{{ $req->industry }}</td>
                                <td>
                                    @php
                                        $badges = [
                                            'new'       => 'primary',
                                            'contacted' => 'warning',
                                            'converted' => 'success',
                                            'rejected'  => 'danger',
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $badges[$req->status] ?? 'secondary' }}">
                                        {{ ucfirst($req->status) }}
                                    </span>
                                </td>
                                <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($req->created_at)->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
                                <td style="white-space:nowrap;">
                                    <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#modal-{{ $req->id }}" title="{{ __('Edit Status') }}">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('demo-inquiries.send-credentials', $req->id) }}" style="display:inline;" onsubmit="return confirm('{{ __('Send login credentials to') }} {{ $req->email }}?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="{{ __('Send Login Credentials') }}">
                                            <i class="ti ti-send"></i> {{ __('Send Login') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            {{-- Status Update Modal --}}
                            <div class="modal fade" id="modal-{{ $req->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('demo-inquiries.status', $req->id) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ $req->name }} — {{ $req->company }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">{{ __('Status') }}</label>
                                                    <select name="status" class="form-select">
                                                        @foreach(['new'=>'New','contacted'=>'Contacted','converted'=>'Converted','rejected'=>'Rejected'] as $val=>$label)
                                                        <option value="{{ $val }}" {{ $req->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">{{ __('Notes') }}</label>
                                                    <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('Internal notes...') }}">{{ $req->notes }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="ti ti-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
                                    {{ __('No demo requests yet.') }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($requests->hasPages())
            <div class="card-footer">
                {{ $requests->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
