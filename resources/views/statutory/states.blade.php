@extends('layouts.admin')
@section('page-title')
    {{ __('State Configuration') }}
@endsection

@section('content')
    @include('statutory._nav')

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('Add State') }}</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('statutory.states.save') }}">
                        @csrf
                        <div class="mb-3"><label class="form-label">{{ __('State Name') }}</label><input type="text" name="state_name" class="form-control" required></div>
                        <button class="btn btn-primary w-100">{{ __('Save State') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('States') }}</h5></div>
                <div class="card-body table-border-style">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>{{ __('ID') }}</th><th>{{ __('State Name') }}</th></tr></thead>
                            <tbody>
                            @forelse($states as $state)
                                <tr><td>{{ $state->id }}</td><td>{{ $state->state_name }}</td></tr>
                            @empty
                                <tr><td colspan="2">{{ __('No states configured.') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

