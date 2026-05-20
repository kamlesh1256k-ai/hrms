@extends('layouts.admin')
@section('page-title')
    {{ __('Statutory Compliance Dashboard') }}
@endsection

@section('content')
    @include('statutory._nav')

    <div class="row">
        <div class="col-md-3">
            <div class="card"><div class="card-body"><h6>{{ __('Components') }}</h6><h4>{{ $components->count() }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><h6>{{ __('Rules') }}</h6><h4>{{ $rulesCount }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><h6>{{ __('States') }}</h6><h4>{{ $statesCount }}</h4></div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body"><h6>{{ __('Employee Configs') }}</h6><h4>{{ $employeeCfgCount }}</h4></div></div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h5 class="mb-0">{{ __('Statutory Components') }}</h5></div>
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Code') }}</th><th>{{ __('Status') }}</th><th>{{ __('Settings') }}</th></tr></thead>
                    <tbody>
                    @foreach($components as $component)
                        <tr>
                            <td>{{ $component->name }}</td>
                            <td>{{ $component->code }}</td>
                            <td>{{ $component->status ? __('Active') : __('Inactive') }}</td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('statutory.component.settings', ['code' => $component->code]) }}">{{ __('Open') }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

