@extends('layouts.admin')
@section('page-title')
    {{ __('Employee Statutory Configuration') }}
@endsection

@section('content')
    @include('statutory._nav')

    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ __('Employee Overrides') }}</h5></div>
        <div class="card-body table-border-style">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>{{ __('Employee') }}</th><th>{{ __('State') }}</th><th>{{ __('UAN') }}</th><th>{{ __('ESIC') }}</th><th>{{ __('PF') }}</th><th>{{ __('ESIC') }}</th><th>{{ __('PT') }}</th><th>{{ __('LWF') }}</th><th>{{ __('Action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($employees as $emp)
                        @php($cfg = $configs->get($emp->id))
                        <tr>
                            <form method="POST" action="{{ route('statutory.employee.config.save') }}">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                                <td>{{ $emp->name }}</td>
                                <td>
                                    <select name="state_id" class="form-control form-control-sm">
                                        <option value="">{{ __('National') }}</option>
                                        @foreach($states as $s)
                                            <option value="{{ $s->id }}" {{ (int)($cfg->state_id ?? 0) === (int)$s->id ? 'selected' : '' }}>{{ $s->state_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="uan_number" value="{{ $cfg->uan_number ?? '' }}" class="form-control form-control-sm"></td>
                                <td><input type="text" name="esic_number" value="{{ $cfg->esic_number ?? '' }}" class="form-control form-control-sm"></td>
                                <td><input type="checkbox" name="pf_enabled" value="1" {{ !empty($cfg) && $cfg->pf_enabled ? 'checked' : '' }}></td>
                                <td><input type="checkbox" name="esic_enabled" value="1" {{ !empty($cfg) && $cfg->esic_enabled ? 'checked' : '' }}></td>
                                <td><input type="checkbox" name="pt_enabled" value="1" {{ !empty($cfg) && $cfg->pt_enabled ? 'checked' : '' }}></td>
                                <td><input type="checkbox" name="lwf_enabled" value="1" {{ !empty($cfg) && $cfg->lwf_enabled ? 'checked' : '' }}></td>
                                <td><button class="btn btn-sm btn-primary">{{ __('Save') }}</button></td>
                            </form>
                        </tr>
                    @empty
                        <tr><td colspan="9">{{ __('No employees found.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

