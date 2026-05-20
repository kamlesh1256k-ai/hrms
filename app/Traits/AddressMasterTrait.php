<?php

namespace App\Traits;

trait AddressMasterTrait
{
    /**
     * Load country/state/city master from JSON for display in tabs.
     */
    protected function getAddressMasterData()
    {
        $path = base_path('database/data/country_state_city.json');
        if (!file_exists($path)) {
            return [
                'countries' => [],
                'statesFlat' => [],
                'citiesFlat' => [],
                'countryNames' => [],
                'stateNames' => [],
                'cityNames' => [],
            ];
        }
        $data = json_decode(file_get_contents($path), true);
        $countries = $data['countries'] ?? [];
        $statesByCountry = $data['states'] ?? [];
        $citiesByState = $data['cities'] ?? [];
        $countryNames = array_column($countries, 'name', 'id');
        $stateNames = [];
        foreach ($statesByCountry as $countryId => $stateList) {
            foreach ($stateList as $s) {
                $stateNames[$s['id']] = $s['name'];
            }
        }
        $statesFlat = [];
        foreach ($statesByCountry as $countryId => $stateList) {
            $cName = $countryNames[$countryId] ?? $countryId;
            foreach ($stateList as $s) {
                $statesFlat[] = ['country_id' => $countryId, 'country_name' => $cName, 'state_id' => $s['id'], 'state_name' => $s['name']];
            }
        }
        $citiesFlat = [];
        foreach ($citiesByState as $stateId => $cityList) {
            $sName = $stateNames[$stateId] ?? $stateId;
            foreach ($cityList as $c) {
                $citiesFlat[] = ['state_id' => $stateId, 'state_name' => $sName, 'city_id' => $c['id'], 'city_name' => $c['name']];
            }
        }
        $cityNames = [];
        foreach ($citiesByState as $stateId => $cityList) {
            foreach ($cityList as $c) {
                $cityNames[$c['id']] = $c['name'];
            }
        }
        return [
            'countries' => $countries,
            'statesFlat' => $statesFlat,
            'citiesFlat' => $citiesFlat,
            'countryNames' => $countryNames,
            'stateNames' => $stateNames,
            'cityNames' => $cityNames,
        ];
    }
}
