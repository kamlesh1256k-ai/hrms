@extends('layouts.admin')
@section('page-title')
    {{ __('Holidays') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Holidays') }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>{{ __('Holiday List') }}</h5>
        <a href="{{ route('holiday.create') }}" class="btn btn-sm btn-primary">{{ __('Create Holiday') }}</a>
    </div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>{{ __('Title') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Location') }}</th>
                    <th>{{ __('Shifts') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($holidays as $h)
                    <tr>
                        <td>{{ $h->title }}</td>
                        <td>{{ $h->holiday_date }}</td>
                        <td>{{ optional($h->location)->name ?? __('All') }}</td>
                        <td>
                            @if($h->shiftMappings->count())
                                {{ $h->shiftMappings->pluck('shift.name')->join(', ') }}
                            @else
                                {{ __('All') }}
                            @endif
                        </td>
                        <td>{{ ucfirst($h->status) }}</td>
                        <td>
                            <a href="{{ route('holiday.edit', $h->id) }}" class="btn btn-sm btn-warning">{{ __('Edit') }}</a>
                            <form action="{{ route('holiday.destroy', $h->id) }}" method="POST" style="display:inline-block">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
