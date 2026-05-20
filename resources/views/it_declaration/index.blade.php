@extends('layouts.admin')
@section('page-title')
    {{ __('Employee IT Declaration') }}
@endsection

@section('action-button')
    <a href="{{ route('it.declaration.create') }}" class="btn btn-sm btn-primary">{{ __('New Declaration') }}</a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ in_array(\Auth::user()->type, ['company', 'super admin']) ? __('All IT Declarations') : __('My IT Declarations') }}</h5>
        </div>
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            @if(in_array(\Auth::user()->type, ['company', 'super admin']))
                                <th>{{ __('Employee') }}</th>
                            @endif
                            <th>{{ __('Financial Year') }}</th>
                            <th>{{ __('Tax Regime') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Updated') }}</th>
                            <th>{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($declarations as $d)
                        <tr>
                            @if(in_array(\Auth::user()->type, ['company', 'super admin']))
                                <td>{{ $d->employee->name ?? ('Emp #' . $d->employee_id) }}</td>
                            @endif
                            <td>{{ $d->financial_year }}</td>
                            <td><span class="badge bg-{{ $d->tax_regime === 'new' ? 'primary' : 'warning' }}">{{ strtoupper($d->tax_regime) }}</span></td>
                            <td><span class="badge bg-{{ $d->declaration_status === 'approved' ? 'success' : ($d->declaration_status === 'submitted' ? 'info' : 'secondary') }}">{{ ucfirst($d->declaration_status) }}</span></td>
                            <td>{{ $d->updated_at->format('d M Y') }}</td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a class="btn btn-sm btn-outline-info" href="{{ route('it.declaration.review.show', $d->id) }}" data-bs-toggle="tooltip" title="{{ __('View') }}"><i class="ti ti-eye"></i></a>
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('it.declaration.edit', $d->id) }}" data-bs-toggle="tooltip" title="{{ __('Edit') }}"><i class="ti ti-edit"></i></a>
                                    <form method="POST" action="{{ route('it.declaration.delete', $d->id) }}" onsubmit="return confirm('Are you sure you want to delete this declaration?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ in_array(\Auth::user()->type, ['company', 'super admin']) ? 6 : 5 }}">{{ __('No declaration found.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

