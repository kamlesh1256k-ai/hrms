@extends('layouts.admin')

@section('page-title')
    {{ __('Manage Branch') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Branch') }}</li>
@endsection

@section('action-button')
@endsection

@php
    $masterData = $masterData ?? ['countries' => [], 'statesFlat' => [], 'citiesFlat' => [], 'countryNames' => [], 'stateNames' => [], 'cityNames' => []];
@endphp

@section('content')
    <div class="row">
        <div class="col-12">
            @include('layouts.hrm_setup')
        </div>
        <div class="col-12">
            <ul class="nav nav-tabs mb-3" id="branchTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="branches-tab" data-bs-toggle="tab" data-bs-target="#branches-pane" type="button" role="tab">{{ __('Branches') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="country-master-tab" data-bs-toggle="tab" data-bs-target="#country-master-pane" type="button" role="tab">{{ __('Country Master') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="state-master-tab" data-bs-toggle="tab" data-bs-target="#state-master-pane" type="button" role="tab">{{ __('State Master') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="city-master-tab" data-bs-toggle="tab" data-bs-target="#city-master-pane" type="button" role="tab">{{ __('City Master') }}</button>
                </li>
            </ul>

            <div class="tab-content" id="branchTabsContent">
                <div class="tab-pane fade show active" id="branches-pane" role="tabpanel">
                    <div class="my-3 d-flex justify-content-end">
                        @can('Create Branch')
                            <a href="#" data-url="{{ route('branch.create') }}" data-ajax-popup="true"
                                data-title="{{ __('Create New Branch') }}" data-bs-toggle="tooltip" title=""
                                class="btn btn-sm btn-primary" data-bs-original-title="{{ __('Create') }}">
                                <i class="ti ti-plus"></i>
                            </a>
                        @endcan
                    </div>
                    <div class="card">
                        <div class="card-body table-border-style">
                            <div class="table-responsive">
                                <table class="table datatable">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Branch') }}</th>
                                            <th>{{ __('Country') }}</th>
                                            <th>{{ __('State') }}</th>
                                            <th>{{ __('City') }}</th>
                                            <th width="200px">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($branches as $branch)
                                            <tr>
                                                <td>{{ $branch->name }}</td>
                                                <td>{{ $masterData['countryNames'][$branch->country] ?? $branch->country ?? '—' }}</td>
                                                <td>{{ $masterData['stateNames'][$branch->state] ?? $branch->state ?? '—' }}</td>
                                                <td>{{ $masterData['cityNames'][$branch->city] ?? $branch->city ?? '—' }}</td>
                                                <td class="Action">
                                                    <div class="dt-buttons">
                                                        <span class="float-start">
                                                            @can('Edit Branch')
                                                                <div class="action-btn me-2">
                                                                    <a href="#" class="btn btn-sm align-items-center bg-info"
                                                                        data-url="{{ route('branch.edit', $branch->id) }}"
                                                                        data-ajax-popup="true" data-title="{{ __('Edit Branch') }}"
                                                                        data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                        data-original-title="{{ __('Edit') }}">
                                                                        <i class="ti ti-pencil text-white"></i>
                                                                    </a>
                                                                </div>
                                                            @endcan
                                                            @can('Delete Branch')
                                                                <div class="action-btn ">
                                                                    {!! Form::open([
                                                                        'method' => 'DELETE',
                                                                        'route' => ['branch.destroy', $branch->id],
                                                                        'id' => 'delete-form-' . $branch->id,
                                                                        ]) !!}
                                                                        <a href="#"
                                                                            class="btn btn-sm  align-items-center bs-pass-para bg-danger"
                                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}"><i
                                                                            class="ti ti-trash text-white text-white"></i></a>
                                                                    {!! Form::close() !!}
                                                                </div>
                                                            @endcan
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                @include('partials.address_master_tabs', ['masterData' => $masterData])
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
$(function() {
    function loadBranchCountries($container, done) {
        $.get('{{ route("employee.address.countries") }}', function(res) {
            var $sel = $container.find('#branch_country');
            $sel.find('option:not(:first)').remove();
            (res.countries || []).forEach(function(c) {
                $sel.append('<option value="' + c.id + '">' + c.name + '</option>');
            });
            if (done) done();
        });
    }
    function loadBranchStates($container, countryId, done) {
        if (!countryId) {
            $container.find('#branch_state').html('<option value="">{{ __("Select State") }}</option>');
            $container.find('#branch_city').html('<option value="">{{ __("Select City") }}</option>');
            if (done) done();
            return;
        }
        $.get('{{ route("employee.address.states") }}', { country_id: countryId }, function(res) {
            var $sel = $container.find('#branch_state');
            $sel.find('option:not(:first)').remove();
            (res.states || []).forEach(function(s) {
                $sel.append('<option value="' + s.id + '">' + s.name + '</option>');
            });
            $container.find('#branch_city').html('<option value="">{{ __("Select City") }}</option>');
            if (done) done();
        });
    }
    function loadBranchCities($container, stateId, done) {
        if (!stateId) {
            $container.find('#branch_city').html('<option value="">{{ __("Select City") }}</option>');
            if (done) done();
            return;
        }
        $.get('{{ route("employee.address.cities") }}', { state_id: stateId }, function(res) {
            var $sel = $container.find('#branch_city');
            $sel.find('option:not(:first)').remove();
            (res.cities || []).forEach(function(c) {
                $sel.append('<option value="' + c.id + '">' + c.name + '</option>');
            });
            if (done) done();
        });
    }
    function initBranchAddressDropdowns($modal) {
        var $container = $modal.closest('.modal');
        loadBranchCountries($container, function() {
            var $form = $container.find('form[data-branch-country]');
            var country = $form.length ? ($form.attr('data-branch-country') || '') : '';
            var state = $form.length ? ($form.attr('data-branch-state') || '') : '';
            var city = $form.length ? ($form.attr('data-branch-city') || '') : '';
            if (country) {
                $container.find('#branch_country').val(country);
                loadBranchStates($container, country, function() {
                    if (state) {
                        $container.find('#branch_state').val(state);
                        loadBranchCities($container, state, function() {
                            if (city) $container.find('#branch_city').val(city);
                        });
                    }
                });
            }
        });
        $container.off('change.branchAddr', '#branch_country').on('change.branchAddr', '#branch_country', function() {
            loadBranchStates($container, $(this).val());
        });
        $container.off('change.branchAddr', '#branch_state').on('change.branchAddr', '#branch_state', function() {
            loadBranchCities($container, $(this).val());
        });
    }
    $(document).on('shown.bs.modal', '.modal', function() {
        var $m = $(this);
        if ($m.find('#branch_country').length) initBranchAddressDropdowns($m);
    });
});
</script>
@endpush
