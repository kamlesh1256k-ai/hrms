@php
    $masterData = $masterData ?? ['countries' => [], 'statesFlat' => [], 'citiesFlat' => [], 'countryNames' => [], 'stateNames' => [], 'cityNames' => []];
@endphp
{{-- Include this after the list tab pane. Pass: masterData, listTabLabel (e.g. 'Branches') --}}
<div class="tab-pane fade" id="country-master-pane" role="tabpanel">
    <div class="card">
        <div class="card-body table-border-style">
            <h6 class="mb-3">{{ __('Country Master') }}</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('#') }}</th>
                            <th>{{ __('Country ID') }}</th>
                            <th>{{ __('Country Name') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($masterData['countries'] as $idx => $c)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ $c['id'] ?? '' }}</td>
                                <td>{{ $c['name'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="tab-pane fade" id="state-master-pane" role="tabpanel">
    <div class="card">
        <div class="card-body table-border-style">
            <h6 class="mb-3">{{ __('State Master') }}</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('#') }}</th>
                            <th>{{ __('Country') }}</th>
                            <th>{{ __('State ID') }}</th>
                            <th>{{ __('State Name') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($masterData['statesFlat'] as $idx => $s)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ $s['country_name'] ?? '' }}</td>
                                <td>{{ $s['state_id'] ?? '' }}</td>
                                <td>{{ $s['state_name'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="tab-pane fade" id="city-master-pane" role="tabpanel">
    <div class="card">
        <div class="card-body table-border-style">
            <h6 class="mb-3">{{ __('City Master') }}</h6>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('#') }}</th>
                            <th>{{ __('State') }}</th>
                            <th>{{ __('City ID') }}</th>
                            <th>{{ __('City Name') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($masterData['citiesFlat'] as $idx => $c)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ $c['state_name'] ?? '' }}</td>
                                <td>{{ $c['city_id'] ?? '' }}</td>
                                <td>{{ $c['city_name'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
