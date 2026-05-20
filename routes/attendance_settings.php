<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('attendance-settings', 'AttendanceSettingsController@index')->name('attendance-settings.index');
    Route::get('attendance-settings/create', 'AttendanceSettingsController@create')->name('attendance-settings.create');
    Route::post('attendance-settings', 'AttendanceSettingsController@store')->name('attendance-settings.store');
    Route::get('attendance-settings/edit', 'AttendanceSettingsController@edit')->name('attendance-settings.edit');
});
