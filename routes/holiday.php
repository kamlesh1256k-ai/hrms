<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('holiday', 'HolidayController@index')->name('holiday.index');
    Route::get('holiday/create', 'HolidayController@create')->name('holiday.create');
    Route::post('holiday', 'HolidayController@store')->name('holiday.store');
    Route::get('holiday/{id}/edit', 'HolidayController@edit')->name('holiday.edit');
    Route::put('holiday/{id}', 'HolidayController@update')->name('holiday.update');
    Route::delete('holiday/{id}', 'HolidayController@destroy')->name('holiday.destroy');
});
