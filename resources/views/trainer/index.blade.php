@extends('layouts.admin')
@section('page-title')
    {{ __('Manage Trainer') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Trainer') }}</li>
@endsection

@section('action-button')
    <a href="#" data-url="{{ route('trainer.file.import') }}" data-ajax-popup="true"
        data-title="{{ __('Import Trainer CSV file') }}" data-bs-toggle="tooltip" title=""
        class="btn btn-sm btn-primary me-1" data-bs-original-title="{{ __('Import') }}">
        <i class="ti ti-file-import"></i>
    </a>

    <a href="{{ route('trainer.export') }}" class="btn btn-sm btn-primary me-1" data-bs-toggle="tooltip"
        data-bs-original-title="{{ __('Export') }}">
        <i class="ti ti-file-export"></i>
    </a>

    @can('Create Trainer')
        <a href="#" data-url="{{ route('trainer.create') }}" data-ajax-popup="true" data-size="lg"
            data-title="{{ __('Create New Trainer') }}" data-bs-toggle="tooltip" title="" class="btn btn-sm btn-primary"
            data-bs-original-title="{{ __('Create') }}">
            <i class="ti ti-plus"></i>
        </a>
    @endcan
@endsection

@php
    $masterData = $masterData ?? ['countryNames' => [], 'stateNames' => [], 'cityNames' => []];
@endphp

@section('content')
    <div class="row">
        <div class="col-12">
            @include('layouts.hrm_setup')
        </div>
        <div class="col-xl-12">
            <ul class="nav nav-tabs mb-3" id="addressMasterTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#list-pane" type="button" role="tab">{{ __('Trainers') }}</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#country-master-pane" type="button" role="tab">{{ __('Country Master') }}</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#state-master-pane" type="button" role="tab">{{ __('State Master') }}</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#city-master-pane" type="button" role="tab">{{ __('City Master') }}</button></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="list-pane" role="tabpanel">
            <div class="card">
                <div class="card-header card-body table-border-style">
                    {{-- <h5> </h5> --}}
                    <div class="table-responsive">
                        <table class="table" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    <th>{{ __('Branch') }}</th>
                                    <th>{{ __('Full Name') }}</th>
                                    <th>{{ __('Contact') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Country') }}</th>
                                    <th>{{ __('State') }}</th>
                                    <th>{{ __('City') }}</th>
                                    @if (Gate::check('Edit Trainer') || Gate::check('Delete Trainer') || Gate::check('Show Trainer'))
                                        <th width="200px">{{ __('Action') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($trainers as $trainer)
                                    <tr>
                                        <td>{{ !empty($trainer->branches) ? $trainer->branches->name : '' }}</td>
                                        <td>{{ $trainer->firstname . ' ' . $trainer->lastname }}</td>
                                        <td>{{ $trainer->contact }}</td>
                                        <td>{{ $trainer->email }}</td>
                                        <td>{{ $masterData['countryNames'][$trainer->country] ?? ($trainer->country ?? '—') }}</td>
                                        <td>{{ $masterData['stateNames'][$trainer->state] ?? ($trainer->state ?? '—') }}</td>
                                        <td>{{ $masterData['cityNames'][$trainer->city] ?? ($trainer->city ?? '—') }}</td>
                                        <td class="Action">
                                            @if (Gate::check('Edit Trainer') || Gate::check('Delete Trainer') || Gate::check('Show Trainer'))
                                                        @can('Show Trainer')
                                                            <div class="action-btn me-2">
                                                                <a href="#" class="mx-3 btn btn-sm bg-warning align-items-center"
                                                                    data-size="lg"
                                                                    data-url="{{ route('trainer.show', $trainer->id) }}"
                                                                    data-ajax-popup="true" data-size="md"
                                                                    data-bs-toggle="tooltip" title=""
                                                                    data-title="{{ __('Trainer Details') }}"
                                                                    data-bs-original-title="{{ __('View') }}">
                                                                    <span class="text-white"><i class="ti ti-eye"></i></span>
                                                                </a>
                                                            </div>
                                                        @endcan


                                                        @can('Edit Trainer')
                                                            <div class="action-btn me-2">
                                                                <a href="#" class="mx-3 btn btn-sm bg-info align-items-center"
                                                                    data-size="lg"
                                                                    data-url="{{ route('trainer.edit', $trainer->id) }}"
                                                                    data-ajax-popup="true" data-size="md"
                                                                    data-bs-toggle="tooltip" title=""
                                                                    data-title="{{ __('Edit Trainer') }}"
                                                                    data-bs-original-title="{{ __('Edit') }}">
                                                                    <span class="text-white"><i class="ti ti-pencil"></i></span>
                                                                </a>
                                                            </div>
                                                        @endcan

                                                        @can('Delete Trainer')
                                                            <div class="action-btn">
                                                                {!! Form::open([
                                                                    'method' => 'DELETE',
                                                                    'route' => ['trainer.destroy', $trainer->id],
                                                                    'id' => 'delete-form-' . $trainer->id,
                                                                ]) !!}
                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm bg-danger align-items-center bs-pass-para"
                                                                    data-bs-toggle="tooltip" title=""
                                                                    data-bs-original-title="Delete" aria-label="Delete"><span class="text-white"><i
                                                                        class="ti ti-trash"></i></span></a>
                                                                </form>
                                                            </div>
                                                        @endcan
                                            @endif
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
