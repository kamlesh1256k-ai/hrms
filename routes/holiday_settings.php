<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('settings/holiday', 'HolidaySettingsController@show')->name('settings.holiday.show');
    Route::post('settings/holiday', 'HolidaySettingsController@store')->name('settings.holiday.store');
});
